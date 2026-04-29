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

    // Each location is independently cached — clearing one does not affect the other.
    Cache::forget(Menu::locationCacheKey('header'));

    expect(Cache::has(Menu::locationCacheKey('footer')))->toBeTrue();

    $footerAgain = Menu::location('footer');
    expect($footerAgain)->not->toBeNull()
        ->and($footerAgain->name)->toBe('Footer Menu');
});

it('does not re-run the location resolution query on cache hit', function () {
    $menu1 = Menu::create(['name' => 'Header', 'is_visible' => true]);
    MenuLocation::create(['menu_id' => $menu1->id, 'location' => 'header']);

    $menu2 = Menu::create(['name' => 'Footer', 'is_visible' => true]);
    MenuLocation::create(['menu_id' => $menu2->id, 'location' => 'footer']);

    Menu::location('header');
    Menu::location('footer');

    // Re-resolve header — the expensive whereRelation join against
    // menu_locations must not run again (that is what the cache is for).
    // The cheap `find($id)` rehydration is intentional and runs on every call.
    $resolutionQueries = 0;
    DB::listen(function ($query) use (&$resolutionQueries) {
        if (str_contains($query->sql, 'menu_locations')) {
            $resolutionQueries++;
        }
    });

    $headerAgain = Menu::location('header');

    expect($headerAgain)->not->toBeNull()
        ->and($headerAgain->name)->toBe('Header')
        ->and($resolutionQueries)->toBe(0, 'menu_locations join should be cached');
});

it('does not invalidate location resolution cache when a menu item is saved', function () {
    $menu = Menu::create(['name' => 'Test', 'is_visible' => true]);
    MenuLocation::create(['menu_id' => $menu->id, 'location' => 'header']);

    Menu::location('header');

    MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'New',
        'order' => 1,
    ]);

    // Items are not cached, so saving them does not need to invalidate the
    // resolution cache. Verify by confirming the menu_locations join is not
    // re-run after the item save.
    $resolutionQueries = 0;
    DB::listen(function ($query) use (&$resolutionQueries) {
        if (str_contains($query->sql, 'menu_locations')) {
            $resolutionQueries++;
        }
    });

    Menu::location('header');
    expect($resolutionQueries)->toBe(0, 'Item saves must not invalidate the resolution cache');
});
