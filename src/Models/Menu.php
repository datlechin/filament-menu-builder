<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_visible' => 'bool',
        ];
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('order');
    }
}
