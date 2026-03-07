<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Resources\MenuResource\Pages\EditMenu;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\User;
use Filament\Actions\DeleteAction;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('can render the edit page', function () {
    $menu = Menu::create(['name' => 'Test Menu']);

    livewire(EditMenu::class, ['record' => $menu->id])->assertSuccessful();
});

it('can retrieve menu data', function () {
    $menu = Menu::create(['name' => 'Test Menu', 'is_visible' => true]);

    livewire(EditMenu::class, ['record' => $menu->id])
        ->assertSet('data.name', 'Test Menu')
        ->assertSet('data.is_visible', true);
});

it('can update a menu', function () {
    $menu = Menu::create(['name' => 'Old Name', 'is_visible' => true]);

    livewire(EditMenu::class, ['record' => $menu->id])
        ->set('data.name', 'New Name')
        ->call('save');

    expect(Menu::find($menu->id)->name)->toBe('New Name');
});

it('validates name is required when updating', function () {
    $menu = Menu::create(['name' => 'Test Menu', 'is_visible' => true]);

    livewire(EditMenu::class, ['record' => $menu->id])
        ->set('data.name', '')
        ->call('save')
        ->assertHasErrors(['data.name' => 'required']);
});

it('can delete a menu', function () {
    $menu = Menu::create(['name' => 'To Delete']);

    livewire(EditMenu::class, ['record' => $menu->id])
        ->callAction(DeleteAction::class)
        ->assertRedirect();

    expect(Menu::find($menu->id))->toBeNull();
});
