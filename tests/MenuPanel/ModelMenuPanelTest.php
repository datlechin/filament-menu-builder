<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Contracts\MenuPanel as MenuPanelContract;
use Datlechin\FilamentMenuBuilder\Contracts\MenuPanelable;
use Datlechin\FilamentMenuBuilder\MenuPanel\ModelMenuPanel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('test_posts', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('slug');
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('test_posts');
});

it('implements MenuPanel contract', function () {
    expect(ModelMenuPanel::make())->toBeInstanceOf(MenuPanelContract::class);
});

it('gets name from the model', function () {
    $panel = ModelMenuPanel::make()->model(TestPost::class);

    expect($panel->getName())->toBe('Blog Posts');
});

it('returns items from the database', function () {
    TestPost::create(['title' => 'First Post', 'slug' => 'first-post']);
    TestPost::create(['title' => 'Second Post', 'slug' => 'second-post']);

    $panel = ModelMenuPanel::make()->model(TestPost::class);
    $items = $panel->getItems();

    expect($items)->toHaveCount(2)
        ->and($items[0]['title'])->toBe('First Post')
        ->and($items[1]['title'])->toBe('Second Post');
});

it('includes linkable type and id in items', function () {
    $post = TestPost::create(['title' => 'My Post', 'slug' => 'my-post']);

    $panel = ModelMenuPanel::make()->model(TestPost::class);
    $items = $panel->getItems();

    expect($items[0]['linkable_type'])->toBe(TestPost::class)
        ->and($items[0]['linkable_id'])->toBe($post->id);
});

it('returns empty items when no records exist', function () {
    $panel = ModelMenuPanel::make()->model(TestPost::class);

    expect($panel->getItems())->toBeEmpty();
});

it('can set sort order', function () {
    $panel = ModelMenuPanel::make()->model(TestPost::class)->sort(5);

    expect($panel->getSort())->toBe(5);
});

it('can set description', function () {
    $panel = ModelMenuPanel::make()->model(TestPost::class)->description('Select blog posts');

    expect($panel->getDescription())->toBe('Select blog posts');
});

it('can set icon', function () {
    $panel = ModelMenuPanel::make()->model(TestPost::class)->icon('heroicon-o-document');

    expect($panel->getIcon())->toBe('heroicon-o-document');
});

it('can be collapsed', function () {
    $panel = ModelMenuPanel::make()->model(TestPost::class)->collapsible();

    expect($panel->isCollapsible())->toBeTrue();
});

it('generates identifier from name', function () {
    $panel = ModelMenuPanel::make()->model(TestPost::class);

    expect($panel->getIdentifier())->toBe('blog-posts');
});

// Test fixture model
class TestPost extends Model implements MenuPanelable
{
    protected $table = 'test_posts';

    protected $guarded = [];

    public function getMenuPanelName(): string
    {
        return 'Blog Posts';
    }

    public function getMenuPanelTitleColumn(): string
    {
        return 'title';
    }

    public function getMenuPanelUrlUsing(): callable
    {
        return fn (self $model) => '/blog/' . $model->slug;
    }

    public function getMenuPanelModifyQueryUsing(): callable
    {
        return fn ($query) => $query;
    }
}
