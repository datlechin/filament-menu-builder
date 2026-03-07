<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Services\MenuItemService;

beforeEach(function () {
    $this->service = new MenuItemService;
    $this->menu = Menu::create(['name' => 'Test Menu']);
});

it('reorders siblings after indent', function () {
    $item1 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'A', 'order' => 1]);
    $item2 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'B', 'order' => 2]);
    $item3 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'C', 'order' => 3]);

    $this->service->indent($item2->id);

    expect($item2->fresh()->parent_id)->toBe($item1->id)
        ->and($item3->fresh()->order)->toBe(2);
});

it('reorders siblings after unindent', function () {
    $parent = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Parent', 'order' => 1]);
    $child1 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'C1', 'order' => 1, 'parent_id' => $parent->id]);
    $child2 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'C2', 'order' => 2, 'parent_id' => $parent->id]);

    $this->service->unindent($child1->id);

    expect($child1->fresh()->parent_id)->toBeNull()
        ->and($child2->fresh()->order)->toBe(1);
});

it('places indented item at end of new parent children', function () {
    $item1 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'A', 'order' => 1]);
    MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Existing child', 'order' => 1, 'parent_id' => $item1->id]);
    $item2 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'B', 'order' => 2]);

    $this->service->indent($item2->id);

    expect($item2->fresh()->parent_id)->toBe($item1->id)
        ->and($item2->fresh()->order)->toBe(2);
});

it('places unindented item at end of new sibling level', function () {
    $parent = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Parent', 'order' => 1]);
    $sibling = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Sibling', 'order' => 2]);
    $child = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Child', 'order' => 1, 'parent_id' => $parent->id]);

    $this->service->unindent($child->id);

    expect($child->fresh()->parent_id)->toBeNull()
        ->and($child->fresh()->order)->toBe(3);
});

it('gets previous sibling only within same parent', function () {
    $parent1 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'P1', 'order' => 1]);
    $parent2 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'P2', 'order' => 2]);
    $child1 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'C1', 'order' => 1, 'parent_id' => $parent1->id]);
    $child2 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'C2', 'order' => 1, 'parent_id' => $parent2->id]);

    expect($this->service->getPreviousSibling($child2))->toBeNull()
        ->and($this->service->getPreviousSibling($child1))->toBeNull();
});

it('gets previous sibling only within same menu', function () {
    $otherMenu = Menu::create(['name' => 'Other Menu']);
    MenuItem::create(['menu_id' => $otherMenu->id, 'title' => 'Other', 'order' => 1]);
    $item = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Mine', 'order' => 1]);

    expect($this->service->getPreviousSibling($item))->toBeNull();
});

it('can update multiple fields at once', function () {
    $item = MenuItem::create([
        'menu_id' => $this->menu->id,
        'title' => 'Original',
        'url' => '/old',
        'icon' => null,
        'classes' => null,
        'order' => 1,
    ]);

    $this->service->update($item->id, [
        'title' => 'Updated',
        'url' => '/new',
        'icon' => 'heroicon-o-star',
        'classes' => 'font-bold text-lg',
    ]);

    $fresh = $item->fresh();

    expect($fresh->title)->toBe('Updated')
        ->and($fresh->url)->toBe('/new')
        ->and($fresh->icon)->toBe('heroicon-o-star')
        ->and($fresh->classes)->toBe('font-bold text-lg');
});

it('handles reordering with three items correctly', function () {
    $item1 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'A', 'order' => 1]);
    $item2 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'B', 'order' => 2]);
    $item3 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'C', 'order' => 3]);

    $this->service->updateOrder([$item3->id, $item1->id, $item2->id]);

    expect($item3->fresh()->order)->toBe(1)
        ->and($item1->fresh()->order)->toBe(2)
        ->and($item2->fresh()->order)->toBe(3);
});

it('preserves order of unaffected items during partial reorder', function () {
    $item1 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'A', 'order' => 1]);
    $item2 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'B', 'order' => 2]);
    $item3 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'C', 'order' => 3]);

    // Only reorder item2 and item3, item1 is not included
    $this->service->updateOrder([$item3->id, $item2->id]);

    expect($item1->fresh()->order)->toBe(1)
        ->and($item3->fresh()->order)->toBe(1)
        ->and($item2->fresh()->order)->toBe(2);
});
