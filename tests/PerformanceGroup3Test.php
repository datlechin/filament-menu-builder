<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Livewire\CreateCustomLink;
use Datlechin\FilamentMenuBuilder\Livewire\CreateCustomText;
use Datlechin\FilamentMenuBuilder\Livewire\MenuPanel;
use Datlechin\FilamentMenuBuilder\MenuPanel\StaticMenuPanel;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Resources\MenuResource\Pages\ListMenus;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\User;
use Illuminate\Support\Facades\DB;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->menu = Menu::create(['name' => 'Test Menu']);
});

// --- max('order') should use DB aggregate, not load all items ---

it('uses a DB aggregate for max order when creating custom link', function () {
    // Create some existing items
    MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'A', 'url' => '/a', 'order' => 1]);
    MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'B', 'url' => '/b', 'order' => 2]);

    $loadedAllItems = false;
    DB::listen(function ($query) use (&$loadedAllItems) {
        // Detect if a SELECT * (without aggregate) is run on menu_items just to get max order
        if (str_contains($query->sql, 'select *')
            && str_contains($query->sql, 'menu_items')
            && ! str_contains($query->sql, 'max')
            && ! str_contains($query->sql, 'insert')
            && str_contains($query->sql, 'parent_id')) {
            $loadedAllItems = true;
        }
    });

    livewire(CreateCustomLink::class, ['menu' => $this->menu])
        ->fillForm([
            'title' => 'New Link',
            'url' => '/new',
            'target' => '_self',
        ])
        ->call('save');

    expect($loadedAllItems)->toBeFalse('Should not load all menu items just to compute max order');
});

it('uses a DB aggregate for max order when creating custom text', function () {
    MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'A', 'order' => 1]);

    $loadedAllItems = false;
    DB::listen(function ($query) use (&$loadedAllItems) {
        if (str_contains($query->sql, 'select *')
            && str_contains($query->sql, 'menu_items')
            && ! str_contains($query->sql, 'max')
            && ! str_contains($query->sql, 'insert')
            && str_contains($query->sql, 'parent_id')) {
            $loadedAllItems = true;
        }
    });

    livewire(CreateCustomText::class, ['menu' => $this->menu])
        ->fillForm(['title' => 'New Text'])
        ->call('save');

    expect($loadedAllItems)->toBeFalse('Should not load all menu items just to compute max order');
});

it('uses a DB aggregate for max order when adding panel items', function () {
    MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Existing', 'url' => '/x', 'order' => 1]);

    $panel = StaticMenuPanel::make('Links')
        ->add('Home', '/');

    $loadedAllItems = false;
    DB::listen(function ($query) use (&$loadedAllItems) {
        if (str_contains($query->sql, 'select *')
            && str_contains($query->sql, 'menu_items')
            && ! str_contains($query->sql, 'max')
            && ! str_contains($query->sql, 'insert')
            && str_contains($query->sql, 'parent_id')) {
            $loadedAllItems = true;
        }
    });

    livewire(MenuPanel::class, ['menu' => $this->menu, 'menuPanel' => $panel])
        ->set('data', ['Home'])
        ->call('add');

    expect($loadedAllItems)->toBeFalse('Should not load all menu items just to compute max order');
});

it('assigns correct order using DB aggregate', function () {
    MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'First', 'url' => '/first', 'order' => 5]);

    livewire(CreateCustomLink::class, ['menu' => $this->menu])
        ->fillForm([
            'title' => 'Second',
            'url' => '/second',
            'target' => '_self',
        ])
        ->call('save');

    $newItem = MenuItem::where('title', 'Second')->first();
    expect($newItem->order)->toBe(6);
});

// --- HasLocationAction should not load entire tables ---

it('only selects id and name when loading menus for location action', function () {
    Menu::create(['name' => 'Menu 1']);
    Menu::create(['name' => 'Menu 2']);

    $selectedColumns = false;
    DB::listen(function ($query) use (&$selectedColumns) {
        if (str_contains($query->sql, 'menus')
            && str_contains($query->sql, '"id"')
            && str_contains($query->sql, '"name"')
            && ! str_contains($query->sql, 'select *')) {
            $selectedColumns = true;
        }
    });

    // Access the getMenus method via the ListMenus page
    $page = new ListMenus;
    $reflection = new ReflectionMethod($page, 'getMenus');
    $reflection->invoke($page);

    expect($selectedColumns)->toBeTrue('Should select only id and name, not all columns');
});
