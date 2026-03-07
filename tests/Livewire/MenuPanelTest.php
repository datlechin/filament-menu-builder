<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Livewire\MenuPanel;
use Datlechin\FilamentMenuBuilder\MenuPanel\StaticMenuPanel;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->menu = Menu::create(['name' => 'Test Menu']);
});

it('can render with a static panel', function () {
    $panel = StaticMenuPanel::make('Test Panel')
        ->add('Home', '/')
        ->add('About', '/about');

    livewire(MenuPanel::class, ['menu' => $this->menu, 'menuPanel' => $panel])
        ->assertSuccessful()
        ->assertSee('Test Panel');
});

it('displays panel items', function () {
    $panel = StaticMenuPanel::make('Links')
        ->add('Home', '/')
        ->add('About', '/about');

    livewire(MenuPanel::class, ['menu' => $this->menu, 'menuPanel' => $panel])
        ->assertSee('Home')
        ->assertSee('About');
});

it('can add selected items to menu', function () {
    $panel = StaticMenuPanel::make('Links')
        ->add('Home', '/')
        ->add('About', '/about');

    livewire(MenuPanel::class, ['menu' => $this->menu, 'menuPanel' => $panel])
        ->set('data', ['Home', 'About'])
        ->call('add')
        ->assertDispatched('menu:created');

    expect(MenuItem::where('menu_id', $this->menu->id)->count())->toBe(2);
});

it('does not add when no items selected', function () {
    $panel = StaticMenuPanel::make('Links')
        ->add('Home', '/');

    livewire(MenuPanel::class, ['menu' => $this->menu, 'menuPanel' => $panel])
        ->set('data', [])
        ->call('add')
        ->assertHasErrors('data');
});

it('resets selection after adding', function () {
    $panel = StaticMenuPanel::make('Links')
        ->add('Home', '/');

    livewire(MenuPanel::class, ['menu' => $this->menu, 'menuPanel' => $panel])
        ->set('data', ['Home'])
        ->call('add')
        ->assertSet('data', []);
});

it('sets correct order when adding items to existing menu', function () {
    MenuItem::create([
        'menu_id' => $this->menu->id,
        'title' => 'Existing',
        'url' => '/existing',
        'order' => 3,
    ]);

    $panel = StaticMenuPanel::make('Links')
        ->add('New Item', '/new');

    livewire(MenuPanel::class, ['menu' => $this->menu, 'menuPanel' => $panel])
        ->set('data', ['New Item'])
        ->call('add');

    $newItem = MenuItem::where('title', 'New Item')->first();

    expect($newItem->order)->toBe(4);
});

it('mounts with panel properties', function () {
    $panel = StaticMenuPanel::make('My Panel')
        ->description('Test description')
        ->icon('heroicon-o-link')
        ->sort(5)
        ->collapsed()
        ->paginate(10);

    livewire(MenuPanel::class, ['menu' => $this->menu, 'menuPanel' => $panel])
        ->assertSet('name', 'My Panel')
        ->assertSet('description', 'Test description')
        ->assertSet('icon', 'heroicon-o-link')
        ->assertSet('collapsible', true)
        ->assertSet('collapsed', true)
        ->assertSet('paginated', true)
        ->assertSet('perPage', 10);
});

it('resolves closure urls during mount', function () {
    $panel = StaticMenuPanel::make('Links')
        ->add('Dynamic', fn () => '/dynamic');

    $component = livewire(MenuPanel::class, ['menu' => $this->menu, 'menuPanel' => $panel]);

    $items = $component->get('items');

    expect($items[0]['url'])->toBe('/dynamic');
});

it('paginates items when enabled', function () {
    $panel = StaticMenuPanel::make('Links')->paginate(2);

    for ($i = 1; $i <= 5; $i++) {
        $panel->add("Item {$i}", "/item-{$i}");
    }

    $component = livewire(MenuPanel::class, ['menu' => $this->menu, 'menuPanel' => $panel]);

    expect($component->instance()->getItems())->toHaveCount(2);
    expect($component->get('page'))->toBe(1);
});

it('can navigate to next page', function () {
    $panel = StaticMenuPanel::make('Links')->paginate(2);

    for ($i = 1; $i <= 5; $i++) {
        $panel->add("Item {$i}", "/item-{$i}");
    }

    livewire(MenuPanel::class, ['menu' => $this->menu, 'menuPanel' => $panel])
        ->assertSet('page', 1)
        ->call('nextPage')
        ->assertSet('page', 2);
});

it('can navigate to previous page', function () {
    $panel = StaticMenuPanel::make('Links')->paginate(2);

    for ($i = 1; $i <= 5; $i++) {
        $panel->add("Item {$i}", "/item-{$i}");
    }

    livewire(MenuPanel::class, ['menu' => $this->menu, 'menuPanel' => $panel])
        ->set('page', 2)
        ->call('previousPage')
        ->assertSet('page', 1);
});

it('cannot go past last page', function () {
    $panel = StaticMenuPanel::make('Links')->paginate(2);

    for ($i = 1; $i <= 4; $i++) {
        $panel->add("Item {$i}", "/item-{$i}");
    }

    livewire(MenuPanel::class, ['menu' => $this->menu, 'menuPanel' => $panel])
        ->set('page', 2)
        ->call('nextPage')
        ->assertSet('page', 2);
});

it('cannot go before first page', function () {
    $panel = StaticMenuPanel::make('Links')->paginate(2);
    $panel->add('Item', '/item');

    livewire(MenuPanel::class, ['menu' => $this->menu, 'menuPanel' => $panel])
        ->call('previousPage')
        ->assertSet('page', 1);
});

it('calculates total pages correctly', function () {
    $panel = StaticMenuPanel::make('Links')->paginate(3);

    for ($i = 1; $i <= 7; $i++) {
        $panel->add("Item {$i}", "/item-{$i}");
    }

    $component = livewire(MenuPanel::class, ['menu' => $this->menu, 'menuPanel' => $panel]);

    expect($component->instance()->getTotalPages())->toBe(3);
});

it('knows when it has pages', function () {
    $panel = StaticMenuPanel::make('Links')->paginate(2);

    for ($i = 1; $i <= 3; $i++) {
        $panel->add("Item {$i}", "/item-{$i}");
    }

    $component = livewire(MenuPanel::class, ['menu' => $this->menu, 'menuPanel' => $panel]);

    expect($component->instance()->hasPages())->toBeTrue();
});

it('knows when it has no pages', function () {
    $panel = StaticMenuPanel::make('Links');
    $panel->add('Item', '/item');

    $component = livewire(MenuPanel::class, ['menu' => $this->menu, 'menuPanel' => $panel]);

    expect($component->instance()->hasPages())->toBeFalse();
});

it('shows empty state when no items', function () {
    $panel = StaticMenuPanel::make('Empty Panel');

    livewire(MenuPanel::class, ['menu' => $this->menu, 'menuPanel' => $panel])
        ->assertSuccessful();
});

it('stores panel identifier when adding items', function () {
    $panel = StaticMenuPanel::make('pages')
        ->add('Home', '/');

    livewire(MenuPanel::class, ['menu' => $this->menu, 'menuPanel' => $panel])
        ->set('data', ['Home'])
        ->call('add');

    $item = MenuItem::where('title', 'Home')->first();

    expect($item->panel)->toBe('pages')
        ->and($item->type)->toBe('pages');
});
