<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Concerns;

use Datlechin\FilamentMenuBuilder\MenuBuilder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasMenuItems
{
    public static function bootHasMenuItems(): void
    {
        static::saved(fn (self $model) => $model->flushMenuCaches());
        static::deleted(fn (self $model) => $model->flushMenuCaches());
    }

    public function menuItems(): MorphMany
    {
        return $this->morphMany(MenuBuilder::menuItemModel(), 'linkable');
    }

    public function flushMenuCaches(): void
    {
        $menuModel = MenuBuilder::menuModel();

        $this->menuItems()
            ->distinct()
            ->pluck('menu_id')
            ->each(fn (int | string $menuId) => $menuModel::flushTreeCache($menuId));
    }
}
