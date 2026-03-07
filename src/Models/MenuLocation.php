<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Models;

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property int $menu_id
 * @property string $location
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Datlechin\FilamentMenuBuilder\Models\Menu $menu
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
        static::saved(fn () => Cache::forget('filament-menu-builder'));
        static::deleted(fn () => Cache::forget('filament-menu-builder'));
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(FilamentMenuBuilderPlugin::get()->getMenuModel());
    }
}
