<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Contracts\MenuPanelable;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('pages', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('slug');
        $table->timestamps();
    });

    $this->menu = Menu::create(['name' => 'Test Menu']);
});

afterEach(function () {
    Schema::dropIfExists('pages');
});

it('can link to a polymorphic model', function () {
    $page = TestPage::create(['title' => 'About', 'slug' => 'about']);

    $item = MenuItem::create([
        'menu_id' => $this->menu->id,
        'title' => 'About Page',
        'linkable_type' => TestPage::class,
        'linkable_id' => $page->id,
        'order' => 1,
    ]);

    expect($item->linkable)->toBeInstanceOf(TestPage::class)
        ->and($item->linkable->title)->toBe('About');
});

it('resolves url from linkable model', function () {
    $page = TestPage::create(['title' => 'About', 'slug' => 'about']);

    $item = MenuItem::create([
        'menu_id' => $this->menu->id,
        'title' => 'About Page',
        'linkable_type' => TestPage::class,
        'linkable_id' => $page->id,
        'order' => 1,
    ]);

    expect($item->url)->toBe('/pages/about');
});

it('returns type from linkable model', function () {
    $page = TestPage::create(['title' => 'About', 'slug' => 'about']);

    $item = MenuItem::create([
        'menu_id' => $this->menu->id,
        'title' => 'About Page',
        'linkable_type' => TestPage::class,
        'linkable_id' => $page->id,
        'order' => 1,
    ]);

    expect($item->type)->toBe('Pages');
});

it('falls back to stored url when linkable is null', function () {
    $item = MenuItem::create([
        'menu_id' => $this->menu->id,
        'title' => 'External Link',
        'url' => 'https://example.com',
        'order' => 1,
    ]);

    expect($item->linkable)->toBeNull()
        ->and($item->url)->toBe('https://example.com');
});

it('returns null linkable for custom link items', function () {
    $item = MenuItem::create([
        'menu_id' => $this->menu->id,
        'title' => 'Custom',
        'url' => '/custom',
        'order' => 1,
    ]);

    expect($item->linkable)->toBeNull()
        ->and($item->linkable_type)->toBeNull()
        ->and($item->linkable_id)->toBeNull();
});

// Test fixture model
class TestPage extends Model implements MenuPanelable
{
    protected $table = 'pages';

    protected $guarded = [];

    public function getMenuPanelName(): string
    {
        return 'Pages';
    }

    public function getMenuPanelTitleColumn(): string
    {
        return 'title';
    }

    public function getMenuPanelUrlUsing(): callable
    {
        return fn (self $model) => '/pages/' . $model->slug;
    }

    public function getMenuPanelModifyQueryUsing(): callable
    {
        return fn ($query) => $query;
    }
}
