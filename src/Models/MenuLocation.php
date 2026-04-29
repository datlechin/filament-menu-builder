<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Models;

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property int $menu_id
 * @property string $location
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Menu $menu
 */
class MenuLocation extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return config('filament-menu-builder.tables.menu_locations', parent::getTable());
    }

    protected static function booted(): void
    {
        static::saved(function (self $location): void {
            Cache::forget(Menu::locationCacheKey($location->location));

            if ($location->wasChanged('location')) {
                $original = $location->getOriginal('location');

                if ($original !== null && $original !== '') {
                    Cache::forget(Menu::locationCacheKey($original));
                }
            }
        });

        static::deleted(
            fn (self $location) => Cache::forget(Menu::locationCacheKey($location->location)),
        );
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(self::resolveMenuModel());
    }

    protected static function resolveMenuModel(): string
    {
        try {
            return FilamentMenuBuilderPlugin::get()->getMenuModel();
        } catch (\Throwable) {
            return Menu::class;
        }
    }
}
