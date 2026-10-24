<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Support\MenuCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

it('ignores a value written for an old version after the cache was forgotten', function () {
    MenuCache::remember('menu-cache-test', null, fn () => 'first');
    $version = Cache::get('menu-cache-test.version');

    MenuCache::forget('menu-cache-test');
    Cache::put("menu-cache-test.{$version}", 'stale');

    expect(MenuCache::remember('menu-cache-test', null, fn () => 'fresh'))->toBe('fresh');
});

it('forgets the cache only once the transaction commits', function () {
    $menu = Menu::factory()->create();
    $item = MenuItem::factory()->for($menu)->create(['title' => 'Before']);
    $menu->tree();

    DB::transaction(function () use ($menu, $item): void {
        $item->update(['title' => 'After']);

        expect(collect($menu->tree()->nodes())->pluck('title')->all())->toBe(['Before']);
    });

    expect(collect($menu->tree()->nodes())->pluck('title')->all())->toBe(['After']);
});

it('refreshes both menus when an item moves to another menu', function () {
    $from = Menu::factory()->create();
    $to = Menu::factory()->create();
    $item = MenuItem::factory()->for($from)->create(['title' => 'Moving']);
    $from->tree();
    $to->tree();

    $item->update(['menu_id' => $to->id]);

    expect($from->tree()->nodes())->toBe([])
        ->and($to->tree()->nodes()[0]->title)->toBe('Moving');
});
