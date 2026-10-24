<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Support\MenuItemCollection;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\Page;

it('resolves the URL of each destination', function () {
    $page = Page::create(['title' => 'About', 'slug' => 'about']);

    expect(MenuItem::factory()->make(['url' => '/contact'])->getUrl())->toBe('/contact')
        ->and(MenuItem::factory()->route('home')->make()->getUrl())->toBe('http://localhost')
        ->and(MenuItem::factory()->route('pages.show', ['page' => 'faq'])->make()->getUrl())->toBe('http://localhost/pages/faq')
        ->and(MenuItem::factory()->linkedTo($page)->make()->getUrl())->toBe('http://localhost/pages/about')
        ->and(MenuItem::factory()->text()->make()->getUrl())->toBeNull();
});

it('reports a link whose destination is gone as broken', function (Closure $makeItem) {
    $item = $makeItem();

    expect($item->getUrl())->toBeNull()
        ->and($item->isBroken())->toBeTrue();
})->with([
    'unknown route' => fn () => MenuItem::factory()->route('missing')->make(),
    'missing route parameter' => fn () => MenuItem::factory()->route('pages.show')->make(),
    'trashed record' => function () {
        $page = Page::create(['title' => 'Old', 'slug' => 'old']);
        $item = MenuItem::factory()->linkedTo($page)->create();
        $page->delete();

        return $item->fresh();
    },
    'unsafe URL' => fn () => MenuItem::factory()->make(['url' => 'javascript:alert(1)']),
]);

it('does not treat text items as broken', function () {
    expect(MenuItem::factory()->text()->make()->isBroken())->toBeFalse();
});

it('nests items under their parents in order and leaves out orphans and cycles', function () {
    $items = new MenuItemCollection([
        new MenuItem(['id' => 1, 'title' => 'Second', 'order' => 2]),
        new MenuItem(['id' => 2, 'title' => 'First', 'order' => 1]),
        new MenuItem(['id' => 3, 'title' => 'Child B', 'parent_id' => 2, 'order' => 2]),
        new MenuItem(['id' => 4, 'title' => 'Child A', 'parent_id' => 2, 'order' => 1]),
        new MenuItem(['id' => 5, 'title' => 'Orphan', 'parent_id' => 99]),
        new MenuItem(['id' => 6, 'title' => 'Cycle A', 'parent_id' => 7]),
        new MenuItem(['id' => 7, 'title' => 'Cycle B', 'parent_id' => 6]),
    ]);

    $tree = $items->toTree();

    expect($tree->pluck('title')->all())->toBe(['First', 'Second'])
        ->and($tree->first()->children->pluck('title')->all())->toBe(['Child A', 'Child B'])
        ->and($tree->last()->children)->toBeEmpty();
});

it('copies an item with its sub-items into another menu', function () {
    $item = MenuItem::factory()->create(['title' => 'Parent']);
    MenuItem::factory()->childOf($item)->create(['title' => 'Child']);
    $target = Menu::factory()->create();

    $copy = $item->load('children')->duplicateInto($target);

    expect($copy->menu_id)->toBe($target->id)
        ->and($copy->children()->pluck('title')->all())->toBe(['Child'])
        ->and($copy->children()->first()->menu_id)->toBe($target->id);
});

it('deletes its sub-items with it', function () {
    $parent = MenuItem::factory()->create();
    $child = MenuItem::factory()->childOf($parent)->create();
    MenuItem::factory()->childOf($child)->create();

    $parent->delete();

    expect(MenuItem::count())->toBe(0);
});
