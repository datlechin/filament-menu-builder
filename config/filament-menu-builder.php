<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Models\MenuLocation;

return [
    'models' => [
        'menu' => Menu::class,
        'menu_item' => MenuItem::class,
        'menu_location' => MenuLocation::class,
    ],

    'tables' => [
        'menus' => 'menus',
        'menu_items' => 'menu_items',
        'menu_locations' => 'menu_locations',
    ],

    'translations' => [
        // Leave empty for single-language menus.
        'locales' => [],

        'attributes' => [
            'menu' => [],
            'menu_item' => ['title'],
        ],
    ],

    // The model menus belong to when the panel uses Filament tenancy, e.g. App\Models\Team::class.
    'tenant_model' => null,

    'cache' => [
        'enabled' => true,
        'store' => null,
    ],
];
