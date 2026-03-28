<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Models;

use Datlechin\FilamentMenuBuilder\Concerns\ResolvesLocale;
use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property string $name
 * @property bool $is_visible
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection|MenuLocation[] $locations
 * @property-read int|null $locations_count
 * @property-read Collection|MenuItem[] $menuItems
 * @property-read int|null $menuItems_count
 */
class Menu extends Model
{
    use ResolvesLocale;

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

            if ($plugin->isTranslatable() && ! in_array(HasTranslations::class, class_uses_recursive($this))) {
                foreach ($plugin->getTranslatableMenuFields() as $field) {
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
        static::saved(fn () => static::clearLocationCache());
        static::deleted(fn () => static::clearLocationCache());
    }

    public static function clearLocationCache(): void
    {
        $keys = Cache::get('filament-menu-builder.location-keys', []);

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        Cache::forget('filament-menu-builder.location-keys');
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
        $cacheKey = "filament-menu-builder.location.{$location}";

        $menuId = Cache::rememberForever($cacheKey, function () use ($location) {
            return self::query()
                ->where('is_visible', true)
                ->whereRelation('locations', 'location', $location)
                ->value('id');
        });

        if (! $menuId) {
            return null;
        }

        return self::with('menuItems')->find($menuId);
    }
}
