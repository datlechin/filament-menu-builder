<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Models;

use Datlechin\FilamentMenuBuilder\Concerns\HasTranslatableAttributes;
use Datlechin\FilamentMenuBuilder\Database\Factories\MenuFactory;
use Datlechin\FilamentMenuBuilder\MenuBuilder;
use Datlechin\FilamentMenuBuilder\Support\MenuTree;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * @property int|string $id
 * @property string|array<string, string> $name
 * @property bool $is_visible
 */
class Menu extends Model
{
    /** @use HasFactory<MenuFactory> */
    use HasFactory;

    use HasTranslatableAttributes;

    protected $guarded = [];

    public function getTable(): string
    {
        return MenuBuilder::table('menus');
    }

    protected static function translationsConfigKey(): string
    {
        return 'menu';
    }

    protected static function newFactory(): MenuFactory
    {
        return MenuFactory::new();
    }

    protected function casts(): array
    {
        return [
            'is_visible' => 'bool',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn (self $menu) => $menu->flushCaches());
        // Locations are removed by a cascading foreign key, so their cache keys must be read before the delete.
        static::deleting(fn (self $menu) => $menu->flushCaches());
    }

    public function items(): HasMany
    {
        return $this->hasMany(MenuBuilder::menuItemModel())->orderBy('order');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(MenuBuilder::menuLocationModel());
    }

    public function tenant(): BelongsTo
    {
        $tenantModel = MenuBuilder::tenantModel() ?? throw new LogicException('Set [filament-menu-builder.tenant_model] to scope menus to a tenant.');

        return $this->belongsTo($tenantModel, MenuBuilder::tenantForeignKey());
    }

    /**
     * @param  list<string>  $locations
     */
    public function syncLocations(array $locations): void
    {
        $tenantKey = MenuBuilder::tenantForeignKey();

        $this->locations()->whereNotIn('location', $locations)->get()->each->delete();

        foreach ($locations as $location) {
            MenuBuilder::menuLocationModel()::query()->updateOrCreate(
                ['location' => $location, ...($tenantKey ? [$tenantKey => $this->getAttribute($tenantKey)] : [])],
                ['menu_id' => $this->getKey()],
            );
        }
    }

    public function duplicate(): static
    {
        return DB::transaction(function (): static {
            $copy = $this->replicate();
            $copy->save();

            $this->copyItemsTo($copy);

            return $copy;
        });
    }

    public function copyItemsTo(self $menu): void
    {
        $this->items()->get()->toTree()->each(fn (MenuItem $item) => $item->duplicateInto($menu));
    }

    public static function location(string $location, Model | int | string | null $tenant = null): ?static
    {
        $resolveMenuId = fn (): int | string | null => static::query()
            ->where('is_visible', true)
            ->whereHas('locations', function (Builder $query) use ($location, $tenant): void {
                $query->where('location', $location);

                if (MenuBuilder::isTenantAware()) {
                    $query->where(MenuBuilder::tenantForeignKey(), $tenant instanceof Model ? $tenant->getKey() : $tenant);
                }
            })
            ->value('id');

        $cache = MenuBuilder::cache();

        // An empty string is cached for "no menu" so a missing location is not looked up on every request.
        $menuId = $cache
            ? $cache->rememberForever(static::locationCacheKey($location, $tenant), fn (): int | string => $resolveMenuId() ?? '')
            : $resolveMenuId();

        if (blank($menuId)) {
            return null;
        }

        return static::query()->where('is_visible', true)->find($menuId);
    }

    public function tree(?string $locale = null): MenuTree
    {
        $locale ??= app()->getLocale();
        $cache = MenuBuilder::cache();

        if (! $cache) {
            return MenuTree::fromItems($this->items()->with('linkable')->get(), $locale);
        }

        // Generated URLs depend on the locale and on the host, which differs per domain in multi-domain apps.
        $variant = $locale . '|' . url('/');
        $trees = $cache->get(static::treeCacheKey($this->getKey()), []);

        if (! isset($trees[$variant])) {
            $trees[$variant] = MenuTree::fromItems($this->items()->with('linkable')->get(), $locale)->toCacheArray();

            $cache->forever(static::treeCacheKey($this->getKey()), $trees);
        }

        return MenuTree::fromCacheArray($trees[$variant]);
    }

    public function flushCaches(): void
    {
        static::flushTreeCache($this->getKey());

        $tenant = MenuBuilder::isTenantAware() ? $this->getAttribute(MenuBuilder::tenantForeignKey()) : null;

        $this->locations()
            ->pluck('location')
            ->each(fn (string $location) => MenuBuilder::cache()?->forget(static::locationCacheKey($location, $tenant)));
    }

    public static function flushTreeCache(int | string $menuId): void
    {
        MenuBuilder::cache()?->forget(static::treeCacheKey($menuId));
    }

    public static function locationCacheKey(string $location, Model | int | string | null $tenant = null): string
    {
        $tenantKey = $tenant instanceof Model ? $tenant->getKey() : $tenant;

        return 'filament-menu-builder.v3.location.' . ($tenantKey === null ? '' : "{$tenantKey}.") . $location;
    }

    public static function treeCacheKey(int | string $menuId): string
    {
        return "filament-menu-builder.v3.tree.{$menuId}";
    }
}
