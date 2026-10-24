<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Models;

use Datlechin\FilamentMenuBuilder\Concerns\HasTranslatableAttributes;
use Datlechin\FilamentMenuBuilder\Database\Factories\MenuFactory;
use Datlechin\FilamentMenuBuilder\MenuBuilder;
use Datlechin\FilamentMenuBuilder\Support\MenuCache;
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
        static::deleting(function (self $menu): void {
            $menu->flushCaches();

            // Tables created before 1.0 have no foreign keys to cascade the delete.
            $menu->items()->delete();
            $menu->locations()->delete();
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(MenuBuilder::menuItemModel(), 'menu_id')->orderBy('order');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(MenuBuilder::menuLocationModel(), 'menu_id');
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

        // An empty string is cached for "no menu" so a missing location is not looked up on every request.
        $menuId = MenuCache::remember(static::locationCacheKey($location, $tenant), null, fn (): int | string => $resolveMenuId() ?? '');

        if (blank($menuId)) {
            return null;
        }

        return static::query()->where('is_visible', true)->find($menuId);
    }

    public function tree(?string $locale = null): MenuTree
    {
        $locale ??= app()->getLocale();

        // Generated URLs depend on the locale and on the host, which differs per domain in multi-domain apps.
        $nodes = MenuCache::remember(
            static::treeCacheKey($this->getKey()),
            $locale . '.' . hash('xxh3', url('/')),
            fn (): array => MenuTree::fromItems($this->items()->with('linkable')->get(), $locale)->toCacheArray(),
        );

        return MenuTree::fromCacheArray($nodes);
    }

    public function flushCaches(): void
    {
        static::flushTreeCache($this->getKey());

        $tenant = MenuBuilder::isTenantAware() ? $this->getAttribute(MenuBuilder::tenantForeignKey()) : null;

        $this->locations()
            ->pluck('location')
            ->each(fn (string $location) => MenuCache::forget(static::locationCacheKey($location, $tenant)));
    }

    public static function flushTreeCache(int | string $menuId): void
    {
        MenuCache::forget(static::treeCacheKey($menuId));
    }

    public static function locationCacheKey(string $location, Model | int | string | null $tenant = null): string
    {
        $tenantKey = $tenant instanceof Model ? $tenant->getKey() : $tenant;

        return 'filament-menu-builder.location.' . ($tenantKey === null ? '' : "{$tenantKey}.") . $location;
    }

    public static function treeCacheKey(int | string $menuId): string
    {
        return "filament-menu-builder.tree.{$menuId}";
    }
}
