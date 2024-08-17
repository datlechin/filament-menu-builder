<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return config('filament-menu-builder.tables.menus', parent::getTable());
    }

    protected function casts(): array
    {
        return [
            'locations' => 'array',
            'is_visible' => 'bool',
        ];
    }

    public static function location(string $location): ?self
    {
        return static::query()->whereJsonContains('locations', $location)->first();
    }

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)
            ->whereNull('parent_id')
            ->orderBy('parent_id')
            ->orderBy('order')
            ->with('children');
    }
}
