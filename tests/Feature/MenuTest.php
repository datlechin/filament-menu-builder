<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Models\MenuLocation;
use Datlechin\FilamentMenuBuilder\Services\MenuItemService;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\Page;
use Illuminate\Support\Facades\DB;

it('finds the visible menu assigned to a location', function () {
    $menu = Menu::factory()->create();
    $menu->syncLocations(['header']);

    expect(Menu::location('header')?->is($menu))->toBeTrue()
        ->and(Menu::location('footer'))->toBeNull();
});

it('does not return a hidden menu for its location', function () {
    $menu = Menu::factory()->hidden()->create();
    $menu->syncLocations(['header']);

    expect(Menu::location('header'))->toBeNull();
});

it('caches the location lookup and refreshes it when the assignment changes', function () {
    $header = Menu::factory()->create();
    $other = Menu::factory()->create();
    $header->syncLocations(['header']);

    Menu::location('header');

    DB::enableQueryLog();
    Menu::location('header');
    expect(DB::getQueryLog())->toHaveCount(1);

    $other->syncLocations(['header']);
    expect(Menu::location('header')?->is($other))->toBeTrue();

    $other->update(['is_visible' => false]);
    expect(Menu::location('header'))->toBeNull();
});

it('forgets the location when its menu is deleted', function () {
    $menu = Menu::factory()->create();
    $menu->syncLocations(['header']);
    Menu::location('header');

    $menu->delete();

    expect(Menu::location('header'))->toBeNull();
});

it('moves a location from another menu and releases unchecked ones', function () {
    $first = Menu::factory()->create();
    $second = Menu::factory()->create();
    $first->syncLocations(['header', 'footer']);

    $second->syncLocations(['header']);
    $first->syncLocations(['footer']);

    expect(MenuLocation::query()->pluck('menu_id', 'location')->all())
        ->toBe(['header' => $second->id, 'footer' => $first->id]);
});

it('duplicates a menu with its items', function () {
    $menu = Menu::factory()->create(['name' => 'Main']);
    $parent = MenuItem::factory()->for($menu)->create(['title' => 'Parent']);
    MenuItem::factory()->childOf($parent)->create(['title' => 'Child']);
    $menu->syncLocations(['header']);

    $copy = $menu->duplicate();

    expect($copy->name)->toBe('Main')
        ->and($copy->locations)->toBeEmpty()
        ->and($copy->items()->whereNull('parent_id')->first()->children()->pluck('title')->all())->toBe(['Child']);
});

it('builds a tree of visible items with resolved URLs', function () {
    $menu = Menu::factory()->create();
    $page = Page::create(['title' => 'About us', 'slug' => 'about']);
    MenuItem::factory()->for($menu)->linkedTo($page)->create(['title' => 'About', 'order' => 1]);
    $hidden = MenuItem::factory()->for($menu)->hidden()->create(['order' => 2]);
    MenuItem::factory()->childOf($hidden)->create();
    MenuItem::factory()->for($menu)->route('missing')->create(['order' => 3]);
    MenuItem::factory()->for($menu)->text()->create(['title' => 'Heading', 'order' => 4]);

    $nodes = $menu->tree()->nodes();

    expect($nodes)->toHaveCount(2)
        ->and($nodes[0]->title)->toBe('About')
        ->and($nodes[0]->url)->toBe('http://localhost/pages/about')
        ->and($nodes[0]->type)->toBe('model')
        ->and($nodes[1]->title)->toBe('Heading')
        ->and($nodes[1]->url)->toBeNull();
});

it('serves the tree from the cache until the menu changes', function (Closure $change) {
    $menu = Menu::factory()->create();
    $item = MenuItem::factory()->for($menu)->create(['title' => 'Before']);
    $page = Page::create(['title' => 'Page', 'slug' => 'page']);
    MenuItem::factory()->for($menu)->linkedTo($page)->create();

    $menu->tree();
    DB::enableQueryLog();
    $menu->tree();
    expect(DB::getQueryLog())->toBeEmpty();

    $change($menu, $item, $page);

    expect(collect($menu->tree()->nodes())->pluck('title')->all())->not->toContain('Before');
})->with([
    'item saved' => fn (Menu $menu, MenuItem $item) => $item->update(['title' => 'After']),
    'item deleted' => fn (Menu $menu, MenuItem $item) => MenuItemService::make()->delete($menu, $item->id),
    'item hidden' => fn (Menu $menu, MenuItem $item) => $item->update(['is_visible' => false]),
]);

it('refreshes the cached tree when a linked record changes', function () {
    $menu = Menu::factory()->create();
    $page = Page::create(['title' => 'Page', 'slug' => 'old']);
    MenuItem::factory()->for($menu)->linkedTo($page)->create();

    expect($menu->tree()->nodes()[0]->url)->toBe('http://localhost/pages/old');

    $page->update(['slug' => 'new']);

    expect($menu->tree()->nodes()[0]->url)->toBe('http://localhost/pages/new');
});

it('caches the tree per locale', function () {
    config(['filament-menu-builder.translations.locales' => ['en', 'vi']]);
    $menu = Menu::factory()->create();
    MenuItem::factory()->for($menu)->create(['title' => ['en' => 'Home', 'vi' => 'Trang chủ']]);

    expect($menu->tree('en')->nodes()[0]->title)->toBe('Home')
        ->and($menu->tree('vi')->nodes()[0]->title)->toBe('Trang chủ');
});
