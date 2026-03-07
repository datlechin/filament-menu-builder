<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Enums\LinkTarget;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;

it('can create a menu item', function () {
    $menu = Menu::create(['name' => 'Test Menu']);

    $item = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Home',
        'url' => '/',
        'order' => 1,
    ]);

    expect($item)->toBeInstanceOf(MenuItem::class)
        ->and($item->title)->toBe('Home')
        ->and($item->url)->toBe('/');
});

it('uses configured table name', function () {
    $item = new MenuItem;

    expect($item->getTable())->toBe(config('filament-menu-builder.tables.menu_items', 'menu_items'));
});

it('casts order to integer', function () {
    $menu = Menu::create(['name' => 'Test']);

    $item = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Test',
        'order' => '5',
    ]);

    expect($item->order)->toBeInt()->toBe(5);
});

it('casts target to LinkTarget enum', function () {
    $menu = Menu::create(['name' => 'Test']);

    $item = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Test',
        'order' => 1,
        'target' => '_blank',
    ]);

    expect($item->target)->toBe(LinkTarget::Blank);
});

it('defaults target to self', function () {
    $menu = Menu::create(['name' => 'Test']);

    $item = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Test',
        'order' => 1,
    ]);

    $item->refresh();

    expect($item->target)->toBe(LinkTarget::Self);
});

it('belongs to a menu', function () {
    $menu = Menu::create(['name' => 'Test Menu']);

    $item = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Test',
        'order' => 1,
    ]);

    expect($item->menu)->toBeInstanceOf(Menu::class)
        ->and($item->menu->id)->toBe($menu->id);
});

it('can have a parent', function () {
    $menu = Menu::create(['name' => 'Test']);

    $parent = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Parent',
        'order' => 1,
    ]);

    $child = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Child',
        'order' => 1,
        'parent_id' => $parent->id,
    ]);

    expect($child->parent)->toBeInstanceOf(MenuItem::class)
        ->and($child->parent->id)->toBe($parent->id);
});

it('can have children', function () {
    $menu = Menu::create(['name' => 'Test']);

    $parent = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Parent',
        'order' => 1,
    ]);

    MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Child 1',
        'order' => 1,
        'parent_id' => $parent->id,
    ]);

    MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Child 2',
        'order' => 2,
        'parent_id' => $parent->id,
    ]);

    expect($parent->children)->toHaveCount(2);
});

it('orders children by order', function () {
    $menu = Menu::create(['name' => 'Test']);

    $parent = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Parent',
        'order' => 1,
    ]);

    MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Second',
        'order' => 2,
        'parent_id' => $parent->id,
    ]);

    MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'First',
        'order' => 1,
        'parent_id' => $parent->id,
    ]);

    expect($parent->children->first()->title)->toBe('First')
        ->and($parent->children->last()->title)->toBe('Second');
});

it('attempts to delete children when parent is deleted', function () {
    $menu = Menu::create(['name' => 'Test']);

    $parent = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Parent',
        'order' => 1,
    ]);

    $child = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Child',
        'order' => 1,
        'parent_id' => $parent->id,
    ]);

    // Load children before deleting so the model event has them
    $parent->load('children');
    $parent->delete();

    expect(MenuItem::find($parent->id))->toBeNull()
        ->and(MenuItem::find($child->id))->toBeNull();
});

it('returns custom_link type when url is set and no linkable', function () {
    $menu = Menu::create(['name' => 'Test']);

    $item = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Link',
        'url' => 'https://example.com',
        'order' => 1,
    ]);

    expect($item->type)->toBe(__('filament-menu-builder::menu-builder.custom_link'));
});

it('returns custom_text type when no url and no linkable', function () {
    $menu = Menu::create(['name' => 'Test']);

    $item = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Text Only',
        'order' => 1,
    ]);

    expect($item->type)->toBe(__('filament-menu-builder::menu-builder.custom_text'));
});

it('returns panel name as type when panel is set', function () {
    $menu = Menu::create(['name' => 'Test']);

    $item = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Home',
        'url' => '/',
        'panel' => 'pages',
        'order' => 1,
    ]);

    expect($item->type)->toBe('pages');
});

it('can store icon and classes', function () {
    $menu = Menu::create(['name' => 'Test']);

    $item = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Home',
        'url' => '/',
        'icon' => 'heroicon-o-home',
        'classes' => 'text-bold text-red-500',
        'order' => 1,
    ]);

    expect($item->icon)->toBe('heroicon-o-home')
        ->and($item->classes)->toBe('text-bold text-red-500');
});

it('has nullable icon and classes by default', function () {
    $menu = Menu::create(['name' => 'Test']);

    $item = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Home',
        'url' => '/',
        'order' => 1,
    ]);

    expect($item->icon)->toBeNull()
        ->and($item->classes)->toBeNull();
});

it('detects active state for matching url', function () {
    $menu = Menu::create(['name' => 'Test']);

    $item = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Home',
        'url' => '/',
        'order' => 1,
    ]);

    expect($item->isActive(url('/')))->toBeTrue();
});

it('detects inactive state for non-matching url', function () {
    $menu = Menu::create(['name' => 'Test']);

    $item = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'About',
        'url' => '/about',
        'order' => 1,
    ]);

    expect($item->isActive(url('/contact')))->toBeFalse();
});

it('ignores trailing slashes when detecting active state', function () {
    $menu = Menu::create(['name' => 'Test']);

    $item = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'About',
        'url' => '/about/',
        'order' => 1,
    ]);

    expect($item->isActive(url('/about')))->toBeTrue();
});

it('detects active state in children', function () {
    $menu = Menu::create(['name' => 'Test']);

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

    $parent->load('children');

    expect($parent->isActiveOrHasActiveChild(url('/child')))->toBeTrue()
        ->and($parent->isActiveOrHasActiveChild(url('/other')))->toBeFalse();
});

it('returns true for isActiveOrHasActiveChild when self is active', function () {
    $menu = Menu::create(['name' => 'Test']);

    $item = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Home',
        'url' => '/',
        'order' => 1,
    ]);

    expect($item->isActiveOrHasActiveChild(url('/')))->toBeTrue();
});
