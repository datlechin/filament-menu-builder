<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Livewire\MenuItems;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Resources\MenuResource\Pages\EditMenu;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\DB;

use function Pest\Livewire\livewire;

beforeEach(function () {
    config([
        'filament-menu-builder.translations.locales' => ['en', 'vi'],
        'filament-menu-builder.translations.attributes' => ['menu' => ['name'], 'menu_item' => ['title', 'url']],
    ]);

    $this->actingAs(User::factory()->create());
});

it('stores a translation per locale', function () {
    $menu = Menu::factory()->create();

    livewire(MenuItems::class, ['menu' => $menu])
        ->callAction(
            TestAction::make('createItem')->arguments(['type' => 'link']),
            data: ['title' => ['en' => 'About', 'vi' => 'Giới thiệu'], 'url' => ['en' => '/about', 'vi' => '/gioi-thieu']],
        )
        ->assertHasNoErrors();

    expect(MenuItem::first()->getTranslationsOf('title'))->toBe(['en' => 'About', 'vi' => 'Giới thiệu']);
});

it('only requires the first locale', function () {
    livewire(MenuItems::class, ['menu' => Menu::factory()->create()])
        ->callAction(TestAction::make('createItem')->arguments(['type' => 'text']), data: ['title' => ['en' => 'Only English']])
        ->assertHasNoErrors();

    expect(MenuItem::first()->translated('title', 'vi'))->toBe('Only English');
});

it('resolves the title and URL of the current locale with a fallback', function () {
    $menu = Menu::factory()->create();
    MenuItem::factory()->for($menu)->create(['title' => ['en' => 'About', 'vi' => 'Giới thiệu'], 'url' => ['en' => '/about', 'vi' => '/gioi-thieu']]);
    MenuItem::factory()->for($menu)->create(['title' => ['en' => 'Blog'], 'url' => ['en' => '/blog'], 'order' => 1]);

    app()->setLocale('vi');

    expect(collect($menu->tree()->nodes())->map(fn ($node): array => [$node->title, $node->url])->all())
        ->toBe([['Giới thiệu', '/gioi-thieu'], ['Blog', '/blog']]);
});

it('translates the menu name', function () {
    $menu = Menu::factory()->create(['name' => ['en' => 'Main', 'vi' => 'Chính']]);

    livewire(EditMenu::class, ['record' => $menu->getRouteKey()])
        ->assertFormSet(['name' => ['en' => 'Main', 'vi' => 'Chính']]);

    app()->setLocale('vi');

    expect($menu->translated('name'))->toBe('Chính');
});

it('converts existing values with the translatable migration', function () {
    config(['filament-menu-builder.translations.attributes' => ['menu' => [], 'menu_item' => ['title']]]);
    $menu = DB::table('menus')->insertGetId(['name' => 'Main', 'is_visible' => true]);
    DB::table('menu_items')->insert(['menu_id' => $menu, 'title' => 'Home', 'type' => 'link', 'url' => '/']);
    DB::table('menu_items')->insert(['menu_id' => $menu, 'title' => '{"vi":"Trang chủ"}', 'type' => 'link', 'url' => '/']);

    (include __DIR__ . '/../../database/migrations/make_translatable_columns_json.php.stub')->up();

    expect(DB::table('menu_items')->pluck('title')->all())->toBe(['{"en":"Home"}', '{"vi":"Trang chủ"}']);
});
