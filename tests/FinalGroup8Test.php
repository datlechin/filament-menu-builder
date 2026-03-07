<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Livewire\CreateCustomLink;
use Datlechin\FilamentMenuBuilder\Livewire\MenuItems;
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

// --- Order collision: should use atomic insert ---

it('uses atomic order assignment in CreateCustomLink', function () {
    $source = file_get_contents(__DIR__ . '/../src/Livewire/CreateCustomLink.php');

    expect($source)->toContain('DB::transaction')
        ->and($source)->toContain('lockForUpdate');
});

it('uses atomic order assignment in CreateCustomText', function () {
    $source = file_get_contents(__DIR__ . '/../src/Livewire/CreateCustomText.php');

    expect($source)->toContain('DB::transaction')
        ->and($source)->toContain('lockForUpdate');
});

it('uses atomic order assignment in MenuPanel', function () {
    $source = file_get_contents(__DIR__ . '/../src/Livewire/MenuPanel.php');

    expect($source)->toContain('DB::transaction')
        ->and($source)->toContain('lockForUpdate');
});

it('assigns sequential orders correctly with atomic insert', function () {
    MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Existing', 'url' => '/x', 'order' => 5]);

    livewire(CreateCustomLink::class, ['menu' => $this->menu])
        ->fillForm([
            'title' => 'New',
            'url' => '/new',
            'target' => '_self',
        ])
        ->call('save');

    $newItem = MenuItem::where('title', 'New')->first();
    expect($newItem->order)->toBe(6);
});

it('assigns sequential orders for multiple panel items atomically', function () {
    MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Existing', 'url' => '/x', 'order' => 3]);

    $panel = StaticMenuPanel::make('Links')
        ->add('Home', '/')
        ->add('About', '/about');

    livewire(MenuPanel::class, ['menu' => $this->menu, 'menuPanel' => $panel])
        ->set('data', ['Home', 'About'])
        ->call('add');

    $home = MenuItem::where('title', 'Home')->first();
    $about = MenuItem::where('title', 'About')->first();

    expect($home->order)->toBe(4)
        ->and($about->order)->toBe(5);
});

// --- rel attribute support ---

it('has rel column in menu_items migration', function () {
    $source = file_get_contents(__DIR__ . '/../database/migrations/add_rel_to_menu_items_table.php.stub');

    expect($source)->toContain("'rel'");
});

it('can create a menu item with rel attribute', function () {
    $item = MenuItem::create([
        'menu_id' => $this->menu->id,
        'title' => 'External',
        'url' => 'https://example.com',
        'rel' => 'nofollow noopener',
        'order' => 1,
    ]);

    expect($item->rel)->toBe('nofollow noopener');
});

it('shows rel field in create custom link form', function () {
    livewire(CreateCustomLink::class, ['menu' => $this->menu])
        ->assertFormFieldExists('rel');
});

it('shows rel field in edit menu item form', function () {
    $item = MenuItem::create([
        'menu_id' => $this->menu->id,
        'title' => 'Link',
        'url' => '/link',
        'rel' => 'nofollow',
        'order' => 1,
    ]);

    livewire(MenuItems::class, ['menu' => $this->menu])
        ->call('mountAction', 'edit', ['id' => $item->id, 'title' => $item->title])
        ->assertActionDataSet(['rel' => 'nofollow']);
});

it('saves rel attribute via create custom link', function () {
    livewire(CreateCustomLink::class, ['menu' => $this->menu])
        ->fillForm([
            'title' => 'External',
            'url' => 'https://example.com',
            'target' => '_blank',
            'rel' => 'nofollow noopener noreferrer',
        ])
        ->call('save');

    $item = MenuItem::where('title', 'External')->first();
    expect($item->rel)->toBe('nofollow noopener noreferrer');
});

it('includes rel label in language file', function () {
    $translations = include __DIR__ . '/../resources/lang/en/menu-builder.php';

    expect($translations['form'])->toHaveKey('rel');
});
