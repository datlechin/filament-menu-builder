<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\ItemTypes\LinkItemType;
use Datlechin\FilamentMenuBuilder\ItemTypes\ModelItemType;
use Datlechin\FilamentMenuBuilder\ItemTypes\TextItemType;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Services\MenuItemService;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\Page;

beforeEach(function () {
    $this->service = MenuItemService::make();
    $this->menu = Menu::factory()->create();
});

function item(array $attributes = []): MenuItem
{
    return MenuItem::factory()->for(test()->menu)->create($attributes);
}

function titlesUnder(?MenuItem $parent = null): array
{
    return MenuItem::query()
        ->where('menu_id', test()->menu->id)
        ->where('parent_id', $parent?->id)
        ->orderBy('order')
        ->pluck('title')
        ->all();
}

it('creates an item at the end of its parent', function () {
    $parent = item(['title' => 'Parent']);
    item(['title' => 'Existing', 'parent_id' => $parent->id, 'order' => 1]);

    $created = $this->service->create($this->menu, LinkItemType::make(), ['title' => 'New', 'url' => '/new'], $parent->id);

    expect($created)->toHaveCount(1)
        ->and($created[0]->type)->toBe('link')
        ->and(titlesUnder($parent))->toBe(['Existing', 'New']);
});

it('creates one item per selected record', function () {
    $about = Page::create(['title' => 'About', 'slug' => 'about']);
    $team = Page::create(['title' => 'Team', 'slug' => 'team']);

    $this->service->create($this->menu, ModelItemType::make(Page::class), ['records' => [$team->id, $about->id]]);

    expect(MenuItem::query()->orderBy('order')->get(['title', 'type', 'linkable_type', 'linkable_id'])->toArray())->toBe([
        ['title' => 'Team', 'type' => 'model', 'linkable_type' => Page::class, 'linkable_id' => $team->id],
        ['title' => 'About', 'type' => 'model', 'linkable_type' => Page::class, 'linkable_id' => $about->id],
    ]);
});

it('does not create items beyond the max depth or under a disallowed parent', function () {
    $parent = item();

    FilamentMenuBuilderPlugin::get()->maxDepth(0);
    expect($this->service->create($this->menu, TextItemType::make(), ['title' => 'Child'], $parent->id))->toBe([]);

    FilamentMenuBuilderPlugin::get()->maxDepth(null)->allowNesting(fn (MenuItem $item, ?MenuItem $parent): bool => $parent === null || $item->type === 'link');
    expect($this->service->create($this->menu, TextItemType::make(), ['title' => 'Child'], $parent->id))->toBe([])
        ->and($this->service->create($this->menu, LinkItemType::make(), ['title' => 'Child', 'url' => '/c'], $parent->id))->toHaveCount(1);
});

it('does not create items under a parent of another menu', function () {
    $foreign = MenuItem::factory()->create();

    expect($this->service->create($this->menu, TextItemType::make(), ['title' => 'Child'], $foreign->id))->toBe([]);
});

it('indents an item under its previous sibling and renumbers the old siblings', function () {
    $first = item(['title' => 'A', 'order' => 1]);
    $second = item(['title' => 'B', 'order' => 2]);
    item(['title' => 'C', 'order' => 3]);

    expect($this->service->indent($this->menu, $second->id))->toBeTrue()
        ->and(titlesUnder($first))->toBe(['B'])
        ->and(titlesUnder())->toBe(['A', 'C'])
        ->and(MenuItem::where('title', 'C')->value('order'))->toBe(2);
});

it('outdents an item after its parent', function () {
    $parent = item(['title' => 'Parent', 'order' => 1]);
    $child = item(['title' => 'Child', 'parent_id' => $parent->id, 'order' => 1]);
    item(['title' => 'Next', 'order' => 2]);

    expect($this->service->unindent($this->menu, $child->id))->toBeTrue()
        ->and(titlesUnder())->toBe(['Parent', 'Next', 'Child']);
});

it('moves an item up and down among its siblings', function () {
    item(['title' => 'A', 'order' => 1]);
    $b = item(['title' => 'B', 'order' => 2]);
    item(['title' => 'C', 'order' => 3]);

    expect($this->service->moveUp($this->menu, $b->id))->toBeTrue()
        ->and(titlesUnder())->toBe(['B', 'A', 'C'])
        ->and($this->service->moveUp($this->menu, $b->id))->toBeFalse()
        ->and($this->service->moveDown($this->menu, $b->id))->toBeTrue()
        ->and(titlesUnder())->toBe(['A', 'B', 'C']);
});

it('duplicates an item with its sub-items right after it', function () {
    $a = item(['title' => 'A', 'order' => 1]);
    item(['title' => 'A child', 'parent_id' => $a->id]);
    item(['title' => 'B', 'order' => 2]);

    $copy = $this->service->duplicate($this->menu, $a->id);

    expect(titlesUnder())->toBe(['A', 'A', 'B'])
        ->and(titlesUnder($copy))->toBe(['A child']);
});

it('deletes an item with its sub-items and closes the gap', function () {
    $a = item(['title' => 'A', 'order' => 1]);
    $child = item(['title' => 'A child', 'parent_id' => $a->id]);
    item(['title' => 'Grandchild', 'parent_id' => $child->id]);
    item(['title' => 'B', 'order' => 2]);

    expect($this->service->delete($this->menu, $a->id))->toBeTrue()
        ->and(MenuItem::pluck('title')->all())->toBe(['B'])
        ->and(MenuItem::where('title', 'B')->value('order'))->toBe(1);
});

it('does not touch items of another menu', function () {
    $foreign = MenuItem::factory()->create(['title' => 'Foreign']);
    $parent = item();

    expect($this->service->updateOrder($this->menu, [$foreign->id], $parent->id))->toBeFalse()
        ->and($this->service->delete($this->menu, $foreign->id))->toBeFalse()
        ->and($this->service->duplicate($this->menu, $foreign->id))->toBeNull()
        ->and($this->service->indent($this->menu, $foreign->id))->toBeFalse()
        ->and($foreign->fresh()->parent_id)->toBeNull();
});

it('does not move an item into its own descendant', function () {
    $parent = item();
    $child = item(['parent_id' => $parent->id]);

    expect($this->service->updateOrder($this->menu, [$parent->id], $child->id))->toBeFalse();
});

it('enforces the max depth on indent and reorder', function () {
    FilamentMenuBuilderPlugin::get()->maxDepth(1);

    $root = item(['order' => 1]);
    $child = item(['parent_id' => $root->id, 'order' => 1]);
    $sibling = item(['parent_id' => $root->id, 'order' => 2]);
    $other = item(['order' => 2]);

    expect($this->service->indent($this->menu, $sibling->id))->toBeFalse()
        ->and($this->service->updateOrder($this->menu, [$other->id], $child->id))->toBeFalse()
        ->and($this->service->updateOrder($this->menu, [$other->id], $root->id))->toBeTrue();
});

it('resolves the max depth for the menu being edited', function () {
    FilamentMenuBuilderPlugin::get()->maxDepth(fn (Menu $menu): int => $menu->name === 'Flat' ? 0 : 1);
    $this->menu->update(['name' => 'Flat']);

    item(['order' => 1]);
    $second = item(['order' => 2]);

    expect($this->service->indent($this->menu, $second->id))->toBeFalse();
});

it('applies the nesting restriction to indent, outdent and reorder', function () {
    FilamentMenuBuilderPlugin::get()->allowNesting(
        fn (MenuItem $item, ?MenuItem $parent): bool => $parent === null ? $item->type === 'text' : $parent->type === 'text' && $item->type === 'link',
    );

    $group = item(['type' => 'text', 'url' => null, 'order' => 1]);
    $link = item(['order' => 2]);
    $otherGroup = item(['type' => 'text', 'url' => null, 'order' => 3]);
    $nested = item(['parent_id' => $group->id]);

    expect($this->service->indent($this->menu, $link->id))->toBeTrue()
        ->and($this->service->indent($this->menu, $otherGroup->id))->toBeFalse()
        ->and($this->service->unindent($this->menu, $nested->id))->toBeFalse()
        ->and($this->service->updateOrder($this->menu, [$otherGroup->id], $group->id))->toBeFalse();
});

it('still lets nested items move to the root when nesting is disallowed', function () {
    FilamentMenuBuilderPlugin::get()->allowNesting(false);

    $parent = item(['order' => 1]);
    $first = item(['parent_id' => $parent->id, 'order' => 1]);
    $second = item(['parent_id' => $parent->id, 'order' => 2]);

    expect($this->service->unindent($this->menu, $first->id))->toBeTrue()
        ->and($this->service->updateOrder($this->menu, [$parent->id, $first->id, $second->id]))->toBeTrue()
        ->and($this->service->indent($this->menu, $second->id))->toBeFalse();
});
