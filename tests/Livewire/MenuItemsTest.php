<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Livewire\MenuItems;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->menu = Menu::create(['name' => 'Test Menu']);
});

it('can render without items', function () {
    livewire(MenuItems::class, ['menu' => $this->menu])
        ->assertSuccessful();
});

it('can reorder items', function () {
    $item1 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'A', 'order' => 1]);
    $item2 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'B', 'order' => 2]);

    livewire(MenuItems::class, ['menu' => $this->menu])
        ->call('reorder', [$item2->id, $item1->id]);

    expect($item2->fresh()->order)->toBe(1)
        ->and($item1->fresh()->order)->toBe(2);
});

it('can indent an item', function () {
    $item1 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'First', 'order' => 1]);
    $item2 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Second', 'order' => 2]);

    livewire(MenuItems::class, ['menu' => $this->menu])
        ->call('indent', $item2->id);

    expect($item2->fresh()->parent_id)->toBe($item1->id);
});

it('can unindent an item', function () {
    $parent = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Parent', 'order' => 1]);
    $child = MenuItem::create([
        'menu_id' => $this->menu->id,
        'title' => 'Child',
        'order' => 1,
        'parent_id' => $parent->id,
    ]);

    livewire(MenuItems::class, ['menu' => $this->menu])
        ->call('unindent', $child->id);

    expect($child->fresh()->parent_id)->toBeNull();
});

it('can check indent capability', function () {
    $item1 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'First', 'order' => 1]);
    $item2 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Second', 'order' => 2]);

    $component = livewire(MenuItems::class, ['menu' => $this->menu]);
    $instance = $component->instance();

    expect($instance->canIndent($item1->id))->toBeFalse()
        ->and($instance->canIndent($item2->id))->toBeTrue();
});

it('can check unindent capability', function () {
    $parent = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Parent', 'order' => 1]);
    $child = MenuItem::create([
        'menu_id' => $this->menu->id,
        'title' => 'Child',
        'order' => 1,
        'parent_id' => $parent->id,
    ]);

    $component = livewire(MenuItems::class, ['menu' => $this->menu]);
    $instance = $component->instance();

    expect($instance->canUnindent($parent->id))->toBeFalse()
        ->and($instance->canUnindent($child->id))->toBeTrue();
});

it('can mount edit action', function () {
    $item = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Editable', 'url' => '/edit-me', 'order' => 1]);

    livewire(MenuItems::class, ['menu' => $this->menu])
        ->call('mountAction', 'edit', ['id' => $item->id, 'title' => $item->title])
        ->assertSuccessful();
});

it('can update icon and classes via edit action', function () {
    $item = MenuItem::create([
        'menu_id' => $this->menu->id,
        'title' => 'Home',
        'url' => '/',
        'order' => 1,
    ]);

    livewire(MenuItems::class, ['menu' => $this->menu])
        ->callAction('edit', data: [
            'title' => 'Home',
            'url' => '/',
            'icon' => 'heroicon-o-home',
            'classes' => 'font-bold',
            'target' => '_self',
        ], arguments: ['id' => $item->id, 'title' => $item->title]);

    $item->refresh();

    expect($item->icon)->toBe('heroicon-o-home')
        ->and($item->classes)->toBe('font-bold');
});

it('can delete a menu item via action', function () {
    $item = MenuItem::create([
        'menu_id' => $this->menu->id,
        'title' => 'To Delete',
        'url' => '/delete',
        'order' => 1,
    ]);

    livewire(MenuItems::class, ['menu' => $this->menu])
        ->callAction('delete', arguments: ['id' => $item->id, 'title' => $item->title]);

    expect(MenuItem::find($item->id))->toBeNull();
});
