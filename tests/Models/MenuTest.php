<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Models\MenuLocation;
use Illuminate\Support\Facades\Cache;

it('can create a menu', function () {
    $menu = Menu::create(['name' => 'Main Menu', 'is_visible' => true]);

    expect($menu)->toBeInstanceOf(Menu::class)
        ->and($menu->name)->toBe('Main Menu')
        ->and($menu->is_visible)->toBeTrue();
});

it('casts is_visible to boolean', function () {
    $menu = Menu::create(['name' => 'Test', 'is_visible' => 1]);

    expect($menu->is_visible)->toBeBool()->toBeTrue();

    $menu->update(['is_visible' => 0]);
    $menu->refresh();

    expect($menu->is_visible)->toBeBool()->toBeFalse();
});

it('uses configured table name', function () {
    $menu = new Menu;

    expect($menu->getTable())->toBe(config('filament-menu-builder.tables.menus', 'menus'));
});

it('has many menu items', function () {
    $menu = Menu::create(['name' => 'Main Menu']);

    MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Item 1',
        'url' => '/item-1',
        'order' => 1,
    ]);

    MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Item 2',
        'url' => '/item-2',
        'order' => 2,
    ]);

    expect($menu->menuItems)->toHaveCount(2);
});

it('only returns root menu items without parent', function () {
    $menu = Menu::create(['name' => 'Main Menu']);

    $parent = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Parent',
        'url' => '/parent',
        'order' => 1,
    ]);

    MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Child',
        'url' => '/child',
        'order' => 1,
        'parent_id' => $parent->id,
    ]);

    $menu->refresh();

    expect($menu->menuItems)->toHaveCount(1)
        ->and($menu->menuItems->first()->title)->toBe('Parent');
});

it('has many locations', function () {
    $menu = Menu::create(['name' => 'Main Menu']);

    MenuLocation::create([
        'menu_id' => $menu->id,
        'location' => 'header',
    ]);

    expect($menu->locations)->toHaveCount(1);
});

it('can find a menu by location', function () {
    $menu = Menu::create(['name' => 'Header Menu', 'is_visible' => true]);

    MenuLocation::create([
        'menu_id' => $menu->id,
        'location' => 'header',
    ]);

    $found = Menu::location('header');

    expect($found)->not->toBeNull()
        ->and($found->name)->toBe('Header Menu');
});

it('returns null for non-existent location', function () {
    expect(Menu::location('non-existent'))->toBeNull();
});

it('does not return hidden menus for location', function () {
    $menu = Menu::create(['name' => 'Hidden Menu', 'is_visible' => false]);

    MenuLocation::create([
        'menu_id' => $menu->id,
        'location' => 'header',
    ]);

    expect(Menu::location('header'))->toBeNull();
});

it('orders menu items by parent_id and order', function () {
    $menu = Menu::create(['name' => 'Menu']);

    MenuItem::create(['menu_id' => $menu->id, 'title' => 'Second', 'order' => 2]);
    MenuItem::create(['menu_id' => $menu->id, 'title' => 'First', 'order' => 1]);

    $menu->refresh();

    expect($menu->menuItems->first()->title)->toBe('First')
        ->and($menu->menuItems->last()->title)->toBe('Second');
});

it('defaults is_visible to true', function () {
    $menu = Menu::create(['name' => 'Test']);
    $menu->refresh();

    expect($menu->is_visible)->toBeTrue();
});

it('caches menu location lookup', function () {
    $menu = Menu::create(['name' => 'Cached Menu', 'is_visible' => true]);

    MenuLocation::create([
        'menu_id' => $menu->id,
        'location' => 'header',
    ]);

    // First call populates cache
    $found = Menu::location('header');
    expect($found->name)->toBe('Cached Menu');

    // Cache should now exist with per-location key
    expect(Cache::has(Menu::locationCacheKey('header')))->toBeTrue();

    // Second call should return from cache
    $cached = Menu::location('header');
    expect($cached->name)->toBe('Cached Menu');
});

it('caches the menu id, not the eloquent graph', function () {
    $menu = Menu::create(['name' => 'Header', 'is_visible' => true]);

    MenuLocation::create([
        'menu_id' => $menu->id,
        'location' => 'header',
    ]);

    Menu::location('header');

    expect(Cache::get(Menu::locationCacheKey('header')))->toBe($menu->id);
});

it('reflects column updates immediately even when the cache is hit', function () {
    $menu = Menu::create(['name' => 'Original', 'is_visible' => true]);

    MenuLocation::create([
        'menu_id' => $menu->id,
        'location' => 'header',
    ]);

    Menu::location('header');
    expect(Cache::has(Menu::locationCacheKey('header')))->toBeTrue();

    // Non-resolution columns do not invalidate the cache, but the menu is
    // re-queried on every call, so updates are still visible.
    $menu->update(['name' => 'Updated']);
    expect(Cache::has(Menu::locationCacheKey('header')))->toBeTrue();
    expect(Menu::location('header')->name)->toBe('Updated');
});

it('busts cache when menu is deleted', function () {
    $menu = Menu::create(['name' => 'To Delete', 'is_visible' => true]);

    MenuLocation::create([
        'menu_id' => $menu->id,
        'location' => 'header',
    ]);

    Menu::location('header');
    expect(Cache::has(Menu::locationCacheKey('header')))->toBeTrue();

    $menu->delete();
    expect(Cache::has(Menu::locationCacheKey('header')))->toBeFalse();
});

it('busts cache when is_visible toggles', function () {
    $menu = Menu::create(['name' => 'Visible', 'is_visible' => true]);

    MenuLocation::create([
        'menu_id' => $menu->id,
        'location' => 'header',
    ]);

    expect(Menu::location('header'))->not->toBeNull();
    expect(Cache::has(Menu::locationCacheKey('header')))->toBeTrue();

    $menu->update(['is_visible' => false]);

    expect(Cache::has(Menu::locationCacheKey('header')))->toBeFalse();
    expect(Menu::location('header'))->toBeNull();
});

it('does not invalidate location cache when a menu item is saved', function () {
    $menu = Menu::create(['name' => 'Test', 'is_visible' => true]);

    MenuLocation::create([
        'menu_id' => $menu->id,
        'location' => 'header',
    ]);

    Menu::location('header');
    expect(Cache::has(Menu::locationCacheKey('header')))->toBeTrue();

    MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'New Item',
        'url' => '/new',
        'order' => 1,
    ]);

    // Items are not cached, so saving them does not need to invalidate the
    // location → menu_id resolution cache. The new item still appears on the
    // next lookup because menuItems are eagerly re-queried.
    expect(Cache::has(Menu::locationCacheKey('header')))->toBeTrue();
    expect(Menu::location('header')->menuItems)->toHaveCount(1);
});

it('busts cache when menu location is changed', function () {
    $menu = Menu::create(['name' => 'Test', 'is_visible' => true]);

    $location = MenuLocation::create([
        'menu_id' => $menu->id,
        'location' => 'header',
    ]);

    Menu::location('header');
    expect(Cache::has(Menu::locationCacheKey('header')))->toBeTrue();

    $location->delete();
    expect(Cache::has(Menu::locationCacheKey('header')))->toBeFalse();
});

it('busts both old and new keys when a location row is renamed', function () {
    $menu = Menu::create(['name' => 'Test', 'is_visible' => true]);
    $location = MenuLocation::create([
        'menu_id' => $menu->id,
        'location' => 'header',
    ]);

    Menu::location('header');
    expect(Cache::has(Menu::locationCacheKey('header')))->toBeTrue();

    $location->update(['location' => 'footer']);

    expect(Cache::has(Menu::locationCacheKey('header')))->toBeFalse()
        ->and(Cache::has(Menu::locationCacheKey('footer')))->toBeFalse();
});

it('does not crash when MenuItem::menu is accessed outside a panel', function () {
    $menu = Menu::create(['name' => 'Test', 'is_visible' => true]);
    $item = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Home',
        'url' => '/',
        'order' => 1,
    ]);

    expect($item->menu)->toBeInstanceOf(Menu::class)
        ->and($item->menu->is($menu))->toBeTrue();
});

it('survives a cache hit on a serializing driver', function () {
    config()->set('cache.default', 'file');
    Cache::store('file')->flush();

    $menu = Menu::create(['name' => 'Header', 'is_visible' => true]);
    MenuLocation::create([
        'menu_id' => $menu->id,
        'location' => 'header',
    ]);
    MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Home',
        'url' => '/',
        'order' => 1,
    ]);

    // MISS — populates cache.
    expect(Menu::location('header'))->toBeInstanceOf(Menu::class);

    // HIT — must round-trip through serialize/unserialize without producing
    // __PHP_Incomplete_Class. The cached payload is an int, so this is safe
    // regardless of what models live in the menuItems graph.
    expect(Menu::location('header'))->toBeInstanceOf(Menu::class);

    Cache::store('file')->flush();
});
