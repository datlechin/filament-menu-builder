<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Enums\LinkTarget;
use Datlechin\FilamentMenuBuilder\Livewire\MenuItems;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->menu = Menu::create(['name' => 'Test Menu']);
});

it('can render with nested items', function () {
    $parent = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Parent', 'url' => '/parent', 'order' => 1]);
    MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Child', 'url' => '/child', 'order' => 1, 'parent_id' => $parent->id]);

    livewire(MenuItems::class, ['menu' => $this->menu])
        ->assertSuccessful();
});

it('can reorder items within a parent', function () {
    $parent = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Parent', 'order' => 1]);
    $child1 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'C1', 'order' => 1, 'parent_id' => $parent->id]);
    $child2 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'C2', 'order' => 2, 'parent_id' => $parent->id]);

    livewire(MenuItems::class, ['menu' => $this->menu])
        ->call('reorder', [$child2->id, $child1->id], (string) $parent->id);

    expect($child2->fresh()->order)->toBe(1)
        ->and($child1->fresh()->order)->toBe(2)
        ->and($child1->fresh()->parent_id)->toBe($parent->id)
        ->and($child2->fresh()->parent_id)->toBe($parent->id);
});

it('can update title via edit action', function () {
    $item = MenuItem::create([
        'menu_id' => $this->menu->id,
        'title' => 'Old Title',
        'url' => '/page',
        'order' => 1,
    ]);

    livewire(MenuItems::class, ['menu' => $this->menu])
        ->callAction('edit', data: [
            'title' => 'New Title',
            'url' => '/page',
            'target' => '_self',
        ], arguments: ['id' => $item->id, 'title' => $item->title]);

    expect($item->fresh()->title)->toBe('New Title');
});

it('can update target via edit action', function () {
    $item = MenuItem::create([
        'menu_id' => $this->menu->id,
        'title' => 'Link',
        'url' => '/page',
        'order' => 1,
    ]);

    livewire(MenuItems::class, ['menu' => $this->menu])
        ->callAction('edit', data: [
            'title' => 'Link',
            'url' => '/page',
            'target' => '_blank',
        ], arguments: ['id' => $item->id, 'title' => $item->title]);

    expect($item->fresh()->target)->toBe(LinkTarget::Blank);
});

it('can delete a nested item without affecting siblings', function () {
    $parent = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Parent', 'order' => 1]);
    $child1 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'C1', 'order' => 1, 'parent_id' => $parent->id]);
    $child2 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'C2', 'order' => 2, 'parent_id' => $parent->id]);

    livewire(MenuItems::class, ['menu' => $this->menu])
        ->callAction('delete', arguments: ['id' => $child1->id, 'title' => $child1->title]);

    expect(MenuItem::find($child1->id))->toBeNull()
        ->and(MenuItem::find($child2->id))->not->toBeNull()
        ->and(MenuItem::find($parent->id))->not->toBeNull();
});

it('computes menu items from the menu relationship', function () {
    MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'A', 'order' => 1]);
    MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'B', 'order' => 2]);

    $component = livewire(MenuItems::class, ['menu' => $this->menu]);

    expect($component->instance()->menuItems())->toHaveCount(2);
});

it('handles reorder with empty array', function () {
    MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'A', 'order' => 1]);

    livewire(MenuItems::class, ['menu' => $this->menu])
        ->call('reorder', [])
        ->assertSuccessful();
});
