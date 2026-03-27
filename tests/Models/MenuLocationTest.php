<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuLocation;
use Illuminate\Database\QueryException;

it('can create a menu location', function () {
    $menu = Menu::create(['name' => 'Main Menu']);

    $location = MenuLocation::create([
        'menu_id' => $menu->id,
        'location' => 'header',
    ]);

    expect($location)->toBeInstanceOf(MenuLocation::class)
        ->and($location->location)->toBe('header');
});

it('uses configured table name', function () {
    $location = new MenuLocation;

    expect($location->getTable())->toBe(config('filament-menu-builder.tables.menu_locations', 'menu_locations'));
});

it('belongs to a menu', function () {
    $menu = Menu::create(['name' => 'Main Menu']);

    $location = MenuLocation::create([
        'menu_id' => $menu->id,
        'location' => 'header',
    ]);

    expect($location->menu)->toBeInstanceOf(Menu::class)
        ->and($location->menu->id)->toBe($menu->id);
});

it('enforces unique location', function () {
    $menu = Menu::create(['name' => 'Main Menu']);

    MenuLocation::create([
        'menu_id' => $menu->id,
        'location' => 'header',
    ]);

    expect(fn () => MenuLocation::create([
        'menu_id' => $menu->id,
        'location' => 'header',
    ]))->toThrow(QueryException::class);
});
