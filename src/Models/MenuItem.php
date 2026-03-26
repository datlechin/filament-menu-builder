<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Models;

use Datlechin\FilamentMenuBuilder\Concerns\ResolvesLocale;
use Datlechin\FilamentMenuBuilder\Contracts\MenuPanelable;
use Datlechin\FilamentMenuBuilder\Enums\LinkTarget;
use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property int $menu_id
 * @property int|null $parent_id
 * @property string $title
 * @property string|null $url
 * @property string|null $panel
 * @property string|null $type
 * @property string|null $icon
 * @property string|null $classes
 * @property string|null $rel
 * @property string|null $target
 * @property int $order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection|MenuItem[] $children
 * @property-read int|null $children_count
 * @property-read Model|MenuPanelable|null $linkable
 * @property-read Menu $menu
 * @property-read MenuItem|null $parent
 */
class MenuItem extends Model
{
    use ResolvesLocale;

    protected $guarded = [];

    protected $with = ['linkable'];

    public function getTable(): string
    {
        return config('filament-menu-builder.tables.menu_items', parent::getTable());
    }

    protected function casts(): array
    {
        $casts = [
            'order' => 'int',
            'target' => LinkTarget::class,
        ];

        try {
            $plugin = FilamentMenuBuilderPlugin::get();

            if ($plugin->isTranslatable() && ! in_array(HasTranslations::class, class_uses_recursive($this))) {
                foreach ($plugin->getTranslatableMenuItemFields() as $field) {
                    $casts[$field] = 'json';
                }
            }
        } catch (\Throwable) {
            // Plugin not registered yet (migration/seeder context)
        }

        return $casts;
    }

    protected static function booted(): void
    {
        static::saved(fn () => Menu::clearLocationCache());
        static::deleted(function (self $menuItem) {
            Menu::clearLocationCache();
            $menuItem->children()->each(fn (self $child) => $child->delete());
        });
    }

    public function isActive(?string $currentUrl = null): bool
    {
        if (is_null($this->url)) {
            return false;
        }

        $currentUrl ??= request()->url();
        $itemUrl = url($this->url);

        return rtrim($currentUrl, '/') === rtrim($itemUrl, '/');
    }

    public function isActiveOrHasActiveChild(?string $currentUrl = null): bool
    {
        $currentUrl ??= request()->url();

        if ($this->isActive($currentUrl)) {
            return true;
        }

        return $this->children->contains(fn (self $child) => $child->isActiveOrHasActiveChild($currentUrl));
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(FilamentMenuBuilderPlugin::get()->getMenuModel());
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id')
            ->with('children')
            ->orderBy('order');
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    protected function url(): Attribute
    {
        return Attribute::get(function (?string $value) {
            return match (true) {
                $this->linkable instanceof MenuPanelable => $this->linkable->getMenuPanelUrlUsing()($this->linkable),
                default => $value,
            };
        });
    }

    protected function type(): Attribute
    {
        return Attribute::get(function () {
            return match (true) {
                $this->linkable instanceof MenuPanelable => $this->linkable->getMenuPanelName(),
                ! is_null($this->panel) => $this->panel,
                is_null($this->url) => __('filament-menu-builder::menu-builder.custom_text'),
                default => __('filament-menu-builder::menu-builder.custom_link'),
            };
        });
    }
}
