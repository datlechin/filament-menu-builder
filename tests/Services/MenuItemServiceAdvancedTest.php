<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
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

    $this->service->indent($this->menu, $item2->id);

    expect($item2->fresh()->parent_id)->toBe($item1->id)
        ->and($item3->fresh()->order)->toBe(2);
});

it('reorders siblings after unindent', function () {
    $parent = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Parent', 'order' => 1]);
    $child1 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'C1', 'order' => 1, 'parent_id' => $parent->id]);
    $child2 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'C2', 'order' => 2, 'parent_id' => $parent->id]);

    $this->service->unindent($this->menu, $child1->id);

    expect($child1->fresh()->parent_id)->toBeNull()
        ->and($child2->fresh()->order)->toBe(1);
});

it('places indented item at end of new parent children', function () {
    $item1 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'A', 'order' => 1]);
    MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Existing child', 'order' => 1, 'parent_id' => $item1->id]);
    $item2 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'B', 'order' => 2]);

    $this->service->indent($this->menu, $item2->id);

    expect($item2->fresh()->parent_id)->toBe($item1->id)
        ->and($item2->fresh()->order)->toBe(2);
});

it('places unindented item at end of new sibling level', function () {
    $parent = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Parent', 'order' => 1]);
    $sibling = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Sibling', 'order' => 2]);
    $child = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Child', 'order' => 1, 'parent_id' => $parent->id]);

    $this->service->unindent($this->menu, $child->id);

    expect($child->fresh()->parent_id)->toBeNull()
        ->and($child->fresh()->order)->toBe(3);
});

it('gets previous sibling only within same parent', function () {
    $parent1 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'P1', 'order' => 1]);
    $parent2 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'P2', 'order' => 2]);
    $child1 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'C1', 'order' => 1, 'parent_id' => $parent1->id]);
    $child2 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'C2', 'order' => 1, 'parent_id' => $parent2->id]);

    $hierarchy = $this->service->hierarchy($this->menu);

    expect($hierarchy->previousSiblingOf($child2->id))->toBeNull()
        ->and($hierarchy->previousSiblingOf($child1->id))->toBeNull();
});

it('gets previous sibling only within same menu', function () {
    $otherMenu = Menu::create(['name' => 'Other Menu']);
    MenuItem::create(['menu_id' => $otherMenu->id, 'title' => 'Other', 'order' => 1]);
    $item = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Mine', 'order' => 1]);

    expect($this->service->hierarchy($this->menu)->previousSiblingOf($item->id))->toBeNull();
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

    $this->service->updateOrder($this->menu, [$item3->id, $item1->id, $item2->id]);

    expect($item3->fresh()->order)->toBe(1)
        ->and($item1->fresh()->order)->toBe(2)
        ->and($item2->fresh()->order)->toBe(3);
});

it('preserves order of unaffected items during partial reorder', function () {
    $item1 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'A', 'order' => 1]);
    $item2 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'B', 'order' => 2]);
    $item3 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'C', 'order' => 3]);

    // Only reorder item2 and item3, item1 is not included
    $this->service->updateOrder($this->menu, [$item3->id, $item2->id]);

    expect($item1->fresh()->order)->toBe(1)
        ->and($item3->fresh()->order)->toBe(1)
        ->and($item2->fresh()->order)->toBe(2);
});

it('does not indent beyond the max depth', function () {
    FilamentMenuBuilderPlugin::get()->maxDepth(1);

    $root = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Root', 'order' => 1]);
    $child = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Child', 'order' => 1, 'parent_id' => $root->id]);
    $sibling = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Sibling', 'order' => 2, 'parent_id' => $root->id]);

    expect($this->service->hierarchy($this->menu)->canIndent($sibling->id))->toBeFalse()
        ->and($this->service->indent($this->menu, $sibling->id))->toBeFalse()
        ->and($sibling->fresh()->parent_id)->toBe($root->id);
});

it('does not reorder into a parent beyond the max depth', function () {
    FilamentMenuBuilderPlugin::get()->maxDepth(1);

    $root = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Root', 'order' => 1]);
    $child = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Child', 'order' => 1, 'parent_id' => $root->id]);
    $item = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Item', 'order' => 2]);

    expect($this->service->updateOrder($this->menu, [$item->id], $child->id))->toBeFalse()
        ->and($item->fresh()->parent_id)->toBeNull()
        ->and($this->service->updateOrder($this->menu, [$child->id, $item->id], $root->id))->toBeTrue()
        ->and($item->fresh()->parent_id)->toBe($root->id);
});

it('resolves the max depth for the menu being edited', function () {
    FilamentMenuBuilderPlugin::get()->maxDepth(fn (Menu $menu): ?int => $menu->is($this->menu) ? 0 : null);

    $first = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'First', 'order' => 1]);
    $second = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Second', 'order' => 2]);

    $otherMenu = Menu::create(['name' => 'Other Menu']);
    MenuItem::create(['menu_id' => $otherMenu->id, 'title' => 'First', 'order' => 1]);
    $otherSecond = MenuItem::create(['menu_id' => $otherMenu->id, 'title' => 'Second', 'order' => 2]);

    expect($this->service->indent($this->menu, $second->id))->toBeFalse()
        ->and($this->service->indent($otherMenu, $otherSecond->id))->toBeTrue();
});

it('does not move items that belong to another menu', function () {
    $otherMenu = Menu::create(['name' => 'Other Menu']);
    $foreign = MenuItem::create(['menu_id' => $otherMenu->id, 'title' => 'Foreign', 'order' => 1]);
    $foreignSibling = MenuItem::create(['menu_id' => $otherMenu->id, 'title' => 'Foreign sibling', 'order' => 2]);
    $parent = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Parent', 'order' => 1]);

    expect($this->service->updateOrder($this->menu, [$foreign->id], $parent->id))->toBeFalse()
        ->and($this->service->updateOrder($otherMenu, [$foreign->id], $parent->id))->toBeFalse()
        ->and($this->service->indent($this->menu, $foreignSibling->id))->toBeFalse()
        ->and($foreign->fresh()->parent_id)->toBeNull()
        ->and($foreignSibling->fresh()->parent_id)->toBeNull();
});

it('does not move an item into its own descendant', function () {
    $parent = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Parent', 'order' => 1]);
    $child = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Child', 'order' => 1, 'parent_id' => $parent->id]);

    expect($this->service->updateOrder($this->menu, [$parent->id], $child->id))->toBeFalse()
        ->and($parent->fresh()->parent_id)->toBeNull();
});

it('only renumbers siblings within the same menu', function () {
    $otherMenu = Menu::create(['name' => 'Other Menu']);
    $foreign = MenuItem::create(['menu_id' => $otherMenu->id, 'title' => 'Foreign', 'order' => 7]);

    MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'A', 'order' => 1]);
    $item = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'B', 'order' => 2]);

    $this->service->indent($this->menu, $item->id);

    expect($foreign->fresh()->order)->toBe(7);
});
