<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;

beforeEach(function () {
    $this->menu = Menu::create(['name' => 'Test Menu']);
});

it('supports three levels of nesting', function () {
    $root = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Root', 'order' => 1]);
    $child = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Child', 'order' => 1, 'parent_id' => $root->id]);
    $grandchild = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Grandchild', 'order' => 1, 'parent_id' => $child->id]);

    $root->load('children.children');

    expect($root->children)->toHaveCount(1)
        ->and($root->children->first()->children)->toHaveCount(1)
        ->and($root->children->first()->children->first()->title)->toBe('Grandchild');
});

it('detects active state in deeply nested children', function () {
    $root = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Root', 'url' => '/root', 'order' => 1]);
    $child = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Child', 'url' => '/child', 'order' => 1, 'parent_id' => $root->id]);
    MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Grandchild', 'url' => '/grandchild', 'order' => 1, 'parent_id' => $child->id]);

    $root->load('children.children');

    expect($root->isActiveOrHasActiveChild(url('/grandchild')))->toBeTrue()
        ->and($root->isActiveOrHasActiveChild(url('/nonexistent')))->toBeFalse();
});

it('cascade deletes through multiple levels', function () {
    $root = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Root', 'order' => 1]);
    $child = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Child', 'order' => 1, 'parent_id' => $root->id]);
    $grandchild = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Grandchild', 'order' => 1, 'parent_id' => $child->id]);

    $root->load('children.children');
    $root->delete();

    expect(MenuItem::find($root->id))->toBeNull()
        ->and(MenuItem::find($child->id))->toBeNull()
        ->and(MenuItem::find($grandchild->id))->toBeNull();
});

it('only returns root-level items via menuItems relationship', function () {
    $root1 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Root 1', 'order' => 1]);
    $root2 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Root 2', 'order' => 2]);
    MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Child', 'order' => 1, 'parent_id' => $root1->id]);

    $menuItems = $this->menu->menuItems;

    expect($menuItems)->toHaveCount(2)
        ->and($menuItems->pluck('title')->all())->toBe(['Root 1', 'Root 2']);
});

it('maintains correct parent reference after re-parenting', function () {
    $parent1 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Parent 1', 'order' => 1]);
    $parent2 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Parent 2', 'order' => 2]);
    $child = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Child', 'order' => 1, 'parent_id' => $parent1->id]);

    $child->update(['parent_id' => $parent2->id]);

    expect($child->fresh()->parent_id)->toBe($parent2->id)
        ->and($parent1->fresh()->children)->toHaveCount(0)
        ->and($parent2->fresh()->children)->toHaveCount(1);
});
