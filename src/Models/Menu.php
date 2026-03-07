<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Models;

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property string $name
 * @property bool $is_visible
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\Datlechin\FilamentMenuBuilder\Models\MenuLocation[] $locations
 * @property-read int|null $locations_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Datlechin\FilamentMenuBuilder\Models\MenuItem[] $menuItems
 * @property-read int|null $menuItems_count
 */
class Menu extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return config('filament-menu-builder.tables.menus', parent::getTable());
    }

    protected function casts(): array
    {
        $casts = [
            'is_visible' => 'bool',
        ];

        try {
            $plugin = FilamentMenuBuilderPlugin::get();

            if ($plugin->isTranslatable() && ! in_array(\Spatie\Translatable\HasTranslations::class, class_uses_recursive($this))) {
                foreach ($plugin->getTranslatableMenuFields() as $field) {
                    $casts[$field] = 'json';
                }
            }
        } catch (\Throwable) {
            // Plugin not registered yet (migration/seeder context)
        }

        return $casts;
    }

    public function resolveLocale(mixed $value): string
    {
        if (is_array($value)) {
            return $value[app()->getLocale()] ?? $value[array_key_first($value)] ?? '';
        }

        return (string) ($value ?? '');
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('filament-menu-builder'));
        static::deleted(fn () => Cache::forget('filament-menu-builder'));
    }

    public function locations(): HasMany
    {
        return $this->hasMany(FilamentMenuBuilderPlugin::get()->getMenuLocationModel());
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(FilamentMenuBuilderPlugin::get()->getMenuItemModel())
            ->whereNull('parent_id')
            ->orderBy('parent_id')
            ->orderBy('order')
            ->with('children');
    }

    public static function location(string $location): ?self
    {
        return Cache::rememberForever('filament-menu-builder', fn () => collect())
            ->get($location, fn () => self::resolveLocation($location));
    }

    protected static function resolveLocation(string $location): ?self
    {
        $menu = self::query()
            ->where('is_visible', true)
            ->whereRelation('locations', 'location', $location)
            ->with('menuItems')
            ->first();

        $cache = Cache::get('filament-menu-builder', collect());
        $cache->put($location, $menu);
        Cache::forever('filament-menu-builder', $cache);

        return $menu;
    }
}
