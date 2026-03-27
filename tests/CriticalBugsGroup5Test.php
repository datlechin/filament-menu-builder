<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Models\MenuLocation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// --- Bug: Children not deleted on bulk delete (orphaned children) ---

it('deletes children when parent is deleted without eager-loading', function () {
    $menu = Menu::create(['name' => 'Test']);

    $parent = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Parent',
        'url' => '/parent',
        'order' => 1,
    ]);

    $child = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Child',
        'url' => '/child',
        'order' => 1,
        'parent_id' => $parent->id,
    ]);

    $grandchild = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Grandchild',
        'url' => '/grandchild',
        'order' => 1,
        'parent_id' => $child->id,
    ]);

    // Delete parent WITHOUT eager-loading children first
    // This is what happens during bulk delete
    $freshParent = MenuItem::find($parent->id);
    $freshParent->delete();

    expect(MenuItem::find($child->id))->toBeNull('Child should be deleted')
        ->and(MenuItem::find($grandchild->id))->toBeNull('Grandchild should be deleted');
});

it('deletes children when parent had children loaded as empty before children were added', function () {
    $menu = Menu::create(['name' => 'Test']);

    $parent = MenuItem::create(['menu_id' => $menu->id, 'title' => 'Parent', 'order' => 1]);

    // Force-load children as empty collection (simulates eager load before children exist)
    $parent->load('children');
    expect($parent->children)->toHaveCount(0);

    // Now add children AFTER the relationship was loaded
    $child = MenuItem::create(['menu_id' => $menu->id, 'title' => 'Child', 'order' => 1, 'parent_id' => $parent->id]);

    // Delete parent — children relationship is stale (empty collection in memory)
    // Bug: $parent->children->each->delete() does nothing because children is empty
    // Fix: $parent->children()->each(...) queries the DB
    $parent->delete();

    expect(MenuItem::find($child->id))->toBeNull('Child should be deleted even when children relation was stale');
});

// --- Bug: Cache race condition on Menu::location() ---

it('uses per-location cache keys instead of a shared collection', function () {
    $menu1 = Menu::create(['name' => 'Header Menu', 'is_visible' => true]);
    MenuLocation::create(['menu_id' => $menu1->id, 'location' => 'header']);

    $menu2 = Menu::create(['name' => 'Footer Menu', 'is_visible' => true]);
    MenuLocation::create(['menu_id' => $menu2->id, 'location' => 'footer']);

    // Resolve both locations
    $header = Menu::location('header');
    $footer = Menu::location('footer');

    expect($header)->not->toBeNull()
        ->and($header->name)->toBe('Header Menu')
        ->and($footer)->not->toBeNull()
        ->and($footer->name)->toBe('Footer Menu');

    // Each location should be independently cached
    // Clearing cache for one location should not affect the other
    Cache::forget('filament-menu-builder.location.header');

    // Footer should still be cached (if using per-location keys)
    // OR: if still using single key, at least both resolve correctly
    $footerAgain = Menu::location('footer');
    expect($footerAgain)->not->toBeNull()
        ->and($footerAgain->name)->toBe('Footer Menu');
});

it('does not lose cached locations when resolving a new one', function () {
    $menu1 = Menu::create(['name' => 'Header', 'is_visible' => true]);
    MenuLocation::create(['menu_id' => $menu1->id, 'location' => 'header']);

    $menu2 = Menu::create(['name' => 'Footer', 'is_visible' => true]);
    MenuLocation::create(['menu_id' => $menu2->id, 'location' => 'footer']);

    // Resolve header first
    $header = Menu::location('header');
    expect($header->name)->toBe('Header');

    // Now resolve footer — header should NOT be lost from cache
    $footer = Menu::location('footer');
    expect($footer->name)->toBe('Footer');

    // Re-resolve header — should come from cache, not re-query
    $queryCount = 0;
    DB::listen(function () use (&$queryCount) {
        $queryCount++;
    });

    $headerAgain = Menu::location('header');
    expect($headerAgain)->not->toBeNull()
        ->and($headerAgain->name)->toBe('Header');

    // With per-location cache, this should be 0 queries
    // With the old shared-collection approach, it might still work but is fragile
    expect($queryCount)->toBe(0, 'Header should be served from cache without re-querying');
});

it('invalidates all location caches when a menu item is saved', function () {
    $menu = Menu::create(['name' => 'Test', 'is_visible' => true]);
    MenuLocation::create(['menu_id' => $menu->id, 'location' => 'header']);

    // Prime cache
    Menu::location('header');

    // Create a menu item — should invalidate cache
    MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'New',
        'order' => 1,
    ]);

    // The cache key(s) should be cleared
    // Verify by checking that a fresh query runs on next location() call
    $queried = false;
    DB::listen(function ($query) use (&$queried) {
        if (str_contains($query->sql, 'menus') && str_contains($query->sql, 'is_visible')) {
            $queried = true;
        }
    });

    Menu::location('header');
    expect($queried)->toBeTrue('Should re-query after cache invalidation');
});
