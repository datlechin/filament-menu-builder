<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Livewire\CreateCustomLink;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->menu = Menu::create(['name' => 'Test Menu']);
});

it('can render', function () {
    livewire(CreateCustomLink::class, ['menu' => $this->menu])
        ->assertSuccessful();
});

it('can create a custom link', function () {
    livewire(CreateCustomLink::class, ['menu' => $this->menu])
        ->set('data.title', 'Google')
        ->set('data.url', 'https://google.com')
        ->set('data.target', '_blank')
        ->call('save')
        ->assertDispatched('menu:changed');

    expect(MenuItem::where('title', 'Google')->exists())->toBeTrue();

    $item = MenuItem::where('title', 'Google')->first();

    expect($item->url)->toBe('https://google.com')
        ->and($item->target->value)->toBe('_blank')
        ->and($item->menu_id)->toBe($this->menu->id);
});

it('validates title is required', function () {
    livewire(CreateCustomLink::class, ['menu' => $this->menu])
        ->set('data.title', '')
        ->set('data.url', 'https://example.com')
        ->call('save')
        ->assertHasErrors(['data.title' => 'required']);
});

it('validates url is required', function () {
    livewire(CreateCustomLink::class, ['menu' => $this->menu])
        ->set('data.title', 'Test')
        ->set('data.url', '')
        ->call('save')
        ->assertHasErrors(['data.url' => 'required']);
});

it('resets form after saving', function () {
    livewire(CreateCustomLink::class, ['menu' => $this->menu])
        ->set('data.title', 'Test Link')
        ->set('data.url', 'https://example.com')
        ->call('save')
        ->assertSet('data.title', null)
        ->assertSet('data.url', null)
        ->assertSet('data.target', '_self');
});

it('sets correct order for new items', function () {
    MenuItem::create([
        'menu_id' => $this->menu->id,
        'title' => 'Existing',
        'url' => '/existing',
        'order' => 3,
    ]);

    livewire(CreateCustomLink::class, ['menu' => $this->menu])
        ->set('data.title', 'New Link')
        ->set('data.url', '/new')
        ->call('save');

    $newItem = MenuItem::where('title', 'New Link')->first();

    expect($newItem->order)->toBe(4);
});

it('defaults target to self', function () {
    livewire(CreateCustomLink::class, ['menu' => $this->menu])
        ->assertSet('data.target', '_self');
});
