<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Models;

use Datlechin\FilamentMenuBuilder\MenuBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int|string $id
 * @property int|string $menu_id
 * @property string $location
 */
class MenuLocation extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return MenuBuilder::table('menu_locations');
    }

    protected static function booted(): void
    {
        static::saved(function (self $location): void {
            $location->forgetCache();

            if ($location->wasChanged('location')) {
                $location->forgetCache($location->getOriginal('location'));
            }
        });

        static::deleted(fn (self $location) => $location->forgetCache());
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(MenuBuilder::menuModel());
    }

    protected function forgetCache(?string $location = null): void
    {
        $tenant = MenuBuilder::isTenantAware() ? $this->getAttribute(MenuBuilder::tenantForeignKey()) : null;

        MenuBuilder::cache()?->forget(MenuBuilder::menuModel()::locationCacheKey($location ?? $this->location, $tenant));
    }
}
