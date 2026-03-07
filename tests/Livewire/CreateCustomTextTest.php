<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Livewire\CreateCustomText;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->menu = Menu::create(['name' => 'Test Menu']);
});

it('can render', function () {
    livewire(CreateCustomText::class, ['menu' => $this->menu])
        ->assertSuccessful();
});

it('can create a custom text item', function () {
    livewire(CreateCustomText::class, ['menu' => $this->menu])
        ->set('data.title', 'Section Header')
        ->call('save')
        ->assertDispatched('menu:created');

    expect(MenuItem::where('title', 'Section Header')->exists())->toBeTrue();

    $item = MenuItem::where('title', 'Section Header')->first();

    expect($item->url)->toBeNull()
        ->and($item->menu_id)->toBe($this->menu->id);
});

it('validates title is required', function () {
    livewire(CreateCustomText::class, ['menu' => $this->menu])
        ->set('data.title', '')
        ->call('save')
        ->assertHasErrors(['data.title' => 'required']);
});

it('resets form after saving', function () {
    livewire(CreateCustomText::class, ['menu' => $this->menu])
        ->set('data.title', 'Test Text')
        ->call('save')
        ->assertSet('data.title', null);
});

it('sets correct order for new items', function () {
    MenuItem::create([
        'menu_id' => $this->menu->id,
        'title' => 'Existing',
        'order' => 5,
    ]);

    livewire(CreateCustomText::class, ['menu' => $this->menu])
        ->set('data.title', 'New Text')
        ->call('save');

    $newItem = MenuItem::where('title', 'New Text')->first();

    expect($newItem->order)->toBe(6);
});
