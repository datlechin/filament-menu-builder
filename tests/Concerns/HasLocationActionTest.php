<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuLocation;
use Datlechin\FilamentMenuBuilder\Resources\MenuResource\Pages\EditMenu;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    FilamentMenuBuilderPlugin::get()->addLocations([
        'header' => 'Header',
        'footer' => 'Footer',
    ]);
});

it('can assign a location to a menu', function () {
    $menu = Menu::create(['name' => 'Main Menu']);

    livewire(EditMenu::class, ['record' => $menu->id])
        ->callAction('locations', data: [
            'header' => ['location' => 'Header', 'menu' => $menu->id],
            'footer' => ['location' => 'Footer', 'menu' => null],
        ]);

    expect(MenuLocation::where('menu_id', $menu->id)->where('location', 'header')->exists())->toBeTrue();
});

it('can remove a location from a menu', function () {
    $menu = Menu::create(['name' => 'Main Menu']);
    MenuLocation::create(['menu_id' => $menu->id, 'location' => 'header']);

    livewire(EditMenu::class, ['record' => $menu->id])
        ->callAction('locations', data: [
            'header' => ['location' => 'Header', 'menu' => null],
            'footer' => ['location' => 'Footer', 'menu' => null],
        ]);

    expect(MenuLocation::where('menu_id', $menu->id)->where('location', 'header')->exists())->toBeFalse();
});

it('can reassign a location from one menu to another', function () {
    $menu1 = Menu::create(['name' => 'Menu 1']);
    $menu2 = Menu::create(['name' => 'Menu 2']);
    MenuLocation::create(['menu_id' => $menu1->id, 'location' => 'header']);

    livewire(EditMenu::class, ['record' => $menu2->id])
        ->callAction('locations', data: [
            'header' => ['location' => 'Header', 'menu' => $menu2->id],
            'footer' => ['location' => 'Footer', 'menu' => null],
        ]);

    expect(MenuLocation::where('location', 'header')->first()->menu_id)->toBe($menu2->id);
});
