<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Resources\MenuResource\Pages\ListMenus;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('can render the list page', function () {
    livewire(ListMenus::class)->assertSuccessful();
});

it('can list menus', function () {
    $menus = collect([
        Menu::create(['name' => 'Main Menu']),
        Menu::create(['name' => 'Footer Menu']),
    ]);

    livewire(ListMenus::class)
        ->assertCanSeeTableRecords($menus);
});

it('can search menus by name', function () {
    Menu::create(['name' => 'Main Menu']);
    Menu::create(['name' => 'Footer Menu']);

    livewire(ListMenus::class)
        ->searchTable('Main')
        ->assertCanSeeTableRecords(Menu::where('name', 'Main Menu')->get())
        ->assertCanNotSeeTableRecords(Menu::where('name', 'Footer Menu')->get());
});

it('can sort menus by name', function () {
    Menu::create(['name' => 'Beta Menu']);
    Menu::create(['name' => 'Alpha Menu']);

    livewire(ListMenus::class)
        ->sortTable('name')
        ->assertCanSeeTableRecords(Menu::orderBy('name')->get(), inOrder: true);
});

it('can sort menus by visibility', function () {
    Menu::create(['name' => 'Visible', 'is_visible' => true]);
    Menu::create(['name' => 'Hidden', 'is_visible' => false]);

    livewire(ListMenus::class)
        ->sortTable('is_visible')
        ->assertSuccessful();
});

it('can create a menu', function () {
    livewire(ListMenus::class)
        ->callAction('create', [
            'name' => 'New Menu',
            'is_visible' => true,
        ])
        ->assertNotified();

    expect(Menu::where('name', 'New Menu')->exists())->toBeTrue();
});

it('validates name is required when creating', function () {
    livewire(ListMenus::class)
        ->callAction('create', [
            'name' => '',
            'is_visible' => true,
        ])
        ->assertHasActionErrors(['name' => 'required']);
});

it('shows menu items count column', function () {
    $menu = Menu::create(['name' => 'Test']);

    livewire(ListMenus::class)
        ->assertCanRenderTableColumn('menu_items_count');
});

it('shows visibility column', function () {
    $menu = Menu::create(['name' => 'Test']);

    livewire(ListMenus::class)
        ->assertCanRenderTableColumn('is_visible');
});

it('shows location column', function () {
    $menu = Menu::create(['name' => 'Test']);

    livewire(ListMenus::class)
        ->assertCanRenderTableColumn('locations.location');
});

it('can delete menus in bulk', function () {
    $menus = collect([
        Menu::create(['name' => 'Menu 1']),
        Menu::create(['name' => 'Menu 2']),
    ]);

    livewire(ListMenus::class)
        ->callTableBulkAction('delete', $menus);

    expect(Menu::count())->toBe(0);
});
