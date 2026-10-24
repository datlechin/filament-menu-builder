<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Models;

use Datlechin\FilamentMenuBuilder\Concerns\HasTranslatableAttributes;
use Datlechin\FilamentMenuBuilder\Contracts\MenuLinkable;
use Datlechin\FilamentMenuBuilder\Database\Factories\MenuItemFactory;
use Datlechin\FilamentMenuBuilder\MenuBuilder;
use Datlechin\FilamentMenuBuilder\Rules\MenuUrl;
use Datlechin\FilamentMenuBuilder\Support\MenuItemCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Support\Facades\Route;

/**
 * @property int|string $id
 * @property int|string $menu_id
 * @property int|string|null $parent_id
 * @property string $type
 * @property string|array<string, string> $title
 * @property string|null $url
 * @property string|null $route
 * @property array<string, mixed>|null $route_parameters
 * @property string|null $linkable_type
 * @property int|string|null $linkable_id
 * @property string|null $target
 * @property string|null $rel
 * @property string|null $icon
 * @property string|null $classes
 * @property array<string, mixed>|null $data
 * @property bool $is_visible
 * @property int $order
 * @property-read MenuItemCollection<int, MenuItem> $children
 * @property-read Model|null $linkable
 */
class MenuItem extends Model
{
    /** @use HasFactory<MenuItemFactory> */
    use HasFactory;

    use HasTranslatableAttributes;

    protected $guarded = [];

    protected $attributes = [
        'is_visible' => true,
        'order' => 0,
    ];

    public function getTable(): string
    {
        return MenuBuilder::table('menu_items');
    }

    protected static function translationsConfigKey(): string
    {
        return 'menu_item';
    }

    protected static function newFactory(): MenuItemFactory
    {
        return MenuItemFactory::new();
    }

    protected function casts(): array
    {
        return [
            'order' => 'int',
            'is_visible' => 'bool',
            'route_parameters' => 'array',
            'data' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn (self $item) => MenuBuilder::menuModel()::flushTreeCache($item->menu_id));
        static::deleted(fn (self $item) => MenuBuilder::menuModel()::flushTreeCache($item->menu_id));
    }

    /**
     * @param  array<int, static>  $models
     * @return MenuItemCollection<int, static>
     */
    public function newCollection(array $models = []): MenuItemCollection
    {
        return new MenuItemCollection($models);
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(MenuBuilder::menuModel());
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id')->orderBy('order');
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    public function duplicateInto(Menu $menu, int | string | null $parentId = null): static
    {
        $copy = $this->replicate();
        $copy->menu_id = $menu->getKey();
        $copy->parent_id = $parentId;
        $copy->save();

        $this->children->each(fn (self $child) => $child->duplicateInto($menu, $copy->getKey()));

        return $copy;
    }

    public function getUrl(?string $locale = null): ?string
    {
        $url = match (true) {
            $this->linkable_type !== null => $this->linkable instanceof MenuLinkable ? $this->linkable->getMenuUrl() : null,
            $this->route !== null => $this->generateRouteUrl(),
            default => $this->translated('url', $locale),
        };

        return filled($url) && MenuUrl::isSafe($url) ? $url : null;
    }

    public function hasDestination(): bool
    {
        return $this->linkable_type !== null || $this->route !== null || filled($this->getAttributes()['url'] ?? null);
    }

    public function isBroken(): bool
    {
        return $this->hasDestination() && $this->getUrl() === null;
    }

    protected function generateRouteUrl(): ?string
    {
        if (! Route::has($this->route)) {
            return null;
        }

        try {
            return route($this->route, $this->route_parameters ?? []);
        } catch (UrlGenerationException) {
            return null;
        }
    }
}
