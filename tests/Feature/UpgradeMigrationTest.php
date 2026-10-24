<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\Page;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('upgrades a v1 menu items table', function () {
    Schema::drop('menu_items');
    Schema::create('menu_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('menu_id');
        $table->foreignId('parent_id')->nullable();
        $table->nullableMorphs('linkable');
        $table->string('panel')->nullable();
        $table->string('title');
        $table->string('url')->nullable();
        $table->string('icon')->nullable();
        $table->string('classes')->nullable();
        $table->string('rel')->nullable();
        $table->string('target', 10)->default('_self');
        $table->integer('order')->default(0);
        $table->timestamps();
    });

    $menu = Menu::factory()->create();
    $page = Page::create(['title' => 'About', 'slug' => 'about']);

    DB::table('menu_items')->insert([
        ['menu_id' => $menu->id, 'title' => 'Home', 'url' => '/', 'panel' => 'pages', 'target' => '_self', 'linkable_type' => null, 'linkable_id' => null, 'order' => 1],
        ['menu_id' => $menu->id, 'title' => 'Docs', 'url' => 'https://docs.example.com', 'panel' => null, 'target' => '_blank', 'linkable_type' => null, 'linkable_id' => null, 'order' => 2],
        ['menu_id' => $menu->id, 'title' => 'Heading', 'url' => null, 'panel' => null, 'target' => '_self', 'linkable_type' => null, 'linkable_id' => null, 'order' => 3],
        ['menu_id' => $menu->id, 'title' => 'About', 'url' => null, 'panel' => Page::class, 'target' => '_self', 'linkable_type' => Page::class, 'linkable_id' => $page->id, 'order' => 4],
    ]);

    (include __DIR__ . '/../../database/migrations/upgrade_menus_table_to_v2.php.stub')->up();

    expect(Schema::hasColumns('menu_items', ['type', 'route', 'route_parameters', 'data', 'is_visible']))->toBeTrue()
        ->and(Schema::hasColumn('menu_items', 'panel'))->toBeFalse()
        ->and(DB::table('menu_items')->orderBy('order')->get(['type', 'target'])->map(fn (object $row): array => (array) $row)->all())->toBe([
            ['type' => 'link', 'target' => null],
            ['type' => 'link', 'target' => '_blank'],
            ['type' => 'text', 'target' => null],
            ['type' => 'model', 'target' => null],
        ])
        ->and(collect($menu->tree()->nodes())->pluck('url')->all())->toBe(['/', 'https://docs.example.com', null, 'http://localhost/pages/about']);
});

it('leaves an up to date table as it is', function () {
    (include __DIR__ . '/../../database/migrations/upgrade_menus_table_to_v2.php.stub')->up();

    expect(Schema::hasColumns('menu_items', ['type', 'route', 'data', 'is_visible']))->toBeTrue();
});
