<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Services\MenuItemService;

beforeEach(function () {
    $this->service = new MenuItemService;
    $this->menu = Menu::create(['name' => 'Test Menu']);
});

it('can find an item by id', function () {
    $item = MenuItem::create([
        'menu_id' => $this->menu->id,
        'title' => 'Test',
        'order' => 1,
    ]);

    expect($this->service->findById($item->id))
        ->toBeInstanceOf(MenuItem::class)
        ->and($this->service->findById($item->id)->title)->toBe('Test');
});

it('returns null for non-existent id', function () {
    expect($this->service->findById(9999))->toBeNull();
});

it('can find an item by id with relations', function () {
    $item = MenuItem::create([
        'menu_id' => $this->menu->id,
        'title' => 'Test',
        'order' => 1,
    ]);

    $found = $this->service->findByIdWithRelations($item->id);

    expect($found)->not->toBeNull()
        ->and($found->relationLoaded('linkable'))->toBeTrue();
});

it('can update item order', function () {
    $item1 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'A', 'order' => 1]);
    $item2 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'B', 'order' => 2]);

    $this->service->updateOrder($this->menu, [$item2->id, $item1->id]);

    expect($item2->fresh()->order)->toBe(1)
        ->and($item1->fresh()->order)->toBe(2);
});

it('sets parent_id when updating order', function () {
    $parent = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Parent', 'order' => 1]);
    $child = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Child', 'order' => 2]);

    $this->service->updateOrder($this->menu, [$child->id], (string) $parent->id);

    expect($child->fresh()->parent_id)->toBe($parent->id);
});

it('does nothing when updating empty order', function () {
    $this->service->updateOrder($this->menu, []);

    expect(true)->toBeTrue();
});

it('can get previous sibling', function () {
    $item1 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'First', 'order' => 1]);
    $item2 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Second', 'order' => 2]);

    expect($this->service->hierarchy($this->menu)->previousSiblingOf($item2->id))->toBe($item1->id);
});

it('returns null when no previous sibling exists', function () {
    $item = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'First', 'order' => 1]);

    expect($this->service->hierarchy($this->menu)->previousSiblingOf($item->id))->toBeNull();
});

it('can get max order for parent', function () {
    MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'A', 'order' => 1]);
    MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'B', 'order' => 5]);

    expect($this->service->getMaxOrderForParent(null))->toBe(5);
});

it('returns zero when no items exist for parent', function () {
    expect($this->service->getMaxOrderForParent(999))->toBe(0);
});

it('can get max order for parent with menu_id filter', function () {
    $otherMenu = Menu::create(['name' => 'Other Menu']);

    MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'A', 'order' => 3]);
    MenuItem::create(['menu_id' => $otherMenu->id, 'title' => 'B', 'order' => 10]);

    expect($this->service->getMaxOrderForParent(null, $this->menu->id))->toBe(3);
});

it('can get siblings', function () {
    MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'A', 'order' => 1]);
    MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'B', 'order' => 2]);

    $siblings = $this->service->getSiblings($this->menu->id, null);

    expect($siblings)->toHaveCount(2);
});

it('can reorder siblings', function () {
    $item1 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'A', 'order' => 5]);
    $item2 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'B', 'order' => 10]);

    $this->service->reorderSiblings($this->menu->id, null);

    expect($item1->fresh()->order)->toBe(1)
        ->and($item2->fresh()->order)->toBe(2);
});

it('can indent an item', function () {
    $item1 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'First', 'order' => 1]);
    $item2 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Second', 'order' => 2]);

    $result = $this->service->indent($this->menu, $item2->id);

    expect($result)->toBeTrue()
        ->and($item2->fresh()->parent_id)->toBe($item1->id);
});

it('cannot indent first item without previous sibling', function () {
    $item = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'First', 'order' => 1]);

    expect($this->service->indent($this->menu, $item->id))->toBeFalse();
});

it('cannot indent non-existent item', function () {
    expect($this->service->indent($this->menu, 9999))->toBeFalse();
});

it('can unindent an item', function () {
    $parent = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Parent', 'order' => 1]);
    $child = MenuItem::create([
        'menu_id' => $this->menu->id,
        'title' => 'Child',
        'order' => 1,
        'parent_id' => $parent->id,
    ]);

    $result = $this->service->unindent($this->menu, $child->id);

    expect($result)->toBeTrue()
        ->and($child->fresh()->parent_id)->toBeNull();
});

it('cannot unindent root level item', function () {
    $item = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Root', 'order' => 1]);

    expect($this->service->unindent($this->menu, $item->id))->toBeFalse();
});

it('cannot unindent non-existent item', function () {
    expect($this->service->unindent($this->menu, 9999))->toBeFalse();
});

it('can check if item can be indented', function () {
    $item1 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'First', 'order' => 1]);
    $item2 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Second', 'order' => 2]);

    expect($this->service->hierarchy($this->menu)->canIndent($item1->id))->toBeFalse()
        ->and($this->service->hierarchy($this->menu)->canIndent($item2->id))->toBeTrue();
});

it('cannot indent non-existent item when checking', function () {
    expect($this->service->hierarchy($this->menu)->canIndent(9999))->toBeFalse();
});

it('can check if item can be unindented', function () {
    $parent = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Parent', 'order' => 1]);
    $child = MenuItem::create([
        'menu_id' => $this->menu->id,
        'title' => 'Child',
        'order' => 1,
        'parent_id' => $parent->id,
    ]);

    expect($this->service->hierarchy($this->menu)->canUnindent($parent->id))->toBeFalse()
        ->and($this->service->hierarchy($this->menu)->canUnindent($child->id))->toBeTrue();
});

it('cannot unindent non-existent item when checking', function () {
    expect($this->service->hierarchy($this->menu)->canUnindent(9999))->toBeFalse();
});

it('can delete an item', function () {
    $item = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Test', 'order' => 1]);

    $result = $this->service->delete($item->id);

    expect($result)->toBeTrue()
        ->and(MenuItem::find($item->id))->toBeNull();
});

it('returns false when deleting non-existent item', function () {
    expect($this->service->delete(9999))->toBeFalse();
});

it('can update an item', function () {
    $item = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Original', 'order' => 1]);

    $result = $this->service->update($item->id, ['title' => 'Updated']);

    expect($result)->toBeTrue()
        ->and($item->fresh()->title)->toBe('Updated');
});

it('returns false when updating non-existent item', function () {
    expect($this->service->update(9999, ['title' => 'Nope']))->toBeFalse();
});
