<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\Livewire\MenuItems;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Models\MenuLocation;
use Datlechin\FilamentMenuBuilder\Resources\MenuResource;
use Datlechin\FilamentMenuBuilder\Resources\MenuResource\Pages\EditMenu;
use Datlechin\FilamentMenuBuilder\Resources\MenuResource\Pages\ListMenus;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\User;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\TextInput;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists menus with their locations and item counts', function () {
    $menu = Menu::factory()->create(['name' => 'Main']);
    $menu->syncLocations(['header']);
    MenuItem::factory()->for($menu)->count(3)->create();

    livewire(ListMenus::class)
        ->assertCanSeeTableRecords([$menu])
        ->assertSee('Header Navigation')
        ->assertTableColumnStateSet('items_count', 3, $menu);
});

it('creates a menu and opens it for editing', function () {
    livewire(ListMenus::class)
        ->callAction('create', data: ['name' => 'Footer', 'locations' => ['footer'], 'is_visible' => true])
        ->assertHasNoErrors()
        ->assertRedirect(MenuResource::getUrl('edit', ['record' => Menu::first()]));

    expect(Menu::first()->locations->pluck('location')->all())->toBe(['footer']);
});

it('renders the item tree beside the menu settings', function () {
    $menu = Menu::factory()->create();

    $this->get(MenuResource::getUrl('edit', ['record' => $menu]))
        ->assertOk()
        ->assertSeeLivewire(MenuItems::class);
});

it('saves the menu settings and moves a location from another menu', function () {
    $other = Menu::factory()->create(['name' => 'Old header']);
    $other->syncLocations(['header']);
    $menu = Menu::factory()->create();

    livewire(EditMenu::class, ['record' => $menu->getRouteKey()])
        ->assertFormFieldExists('locations')
        ->assertSee('Currently shows Old header')
        ->fillForm(['name' => 'New header', 'locations' => ['header'], 'is_visible' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($menu->fresh())
        ->name->toBe('New header')
        ->is_visible->toBeFalse()
        ->and(MenuLocation::where('location', 'header')->value('menu_id'))->toBe($menu->id);
});

it('shows custom menu fields', function () {
    FilamentMenuBuilderPlugin::get()->addMenuFields([TextInput::make('description')]);

    livewire(EditMenu::class, ['record' => Menu::factory()->create()->getRouteKey()])
        ->assertFormFieldExists('description');
});

it('duplicates a menu with its items', function () {
    $menu = Menu::factory()->create(['name' => 'Main']);
    $parent = MenuItem::factory()->for($menu)->create();
    MenuItem::factory()->childOf($parent)->create();

    livewire(ListMenus::class)->callAction(TestAction::make('replicate')->table($menu));

    $copy = Menu::whereKeyNot($menu->id)->first();

    expect($copy->items)->toHaveCount(2)
        ->and($copy->items->firstWhere('parent_id', '!=', null)->parent->menu_id)->toBe($copy->id);
});

it('deletes a menu with its items and locations', function () {
    $menu = Menu::factory()->create();
    $menu->syncLocations(['header']);
    MenuItem::factory()->for($menu)->create();

    livewire(EditMenu::class, ['record' => $menu->getRouteKey()])->callAction('delete');

    expect(Menu::count())->toBe(0)
        ->and(MenuItem::count())->toBe(0)
        ->and(MenuLocation::count())->toBe(0);
});
