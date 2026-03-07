<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\Livewire\CreateCustomLink;
use Datlechin\FilamentMenuBuilder\Livewire\CreateCustomText;
use Datlechin\FilamentMenuBuilder\Livewire\MenuPanel;
use Datlechin\FilamentMenuBuilder\MenuPanel\StaticMenuPanel;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Support\TranslatableFieldWrapper;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\User;
use Filament\Forms\Components\TextInput;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

// --- addMenuFields() should merge, not replace ---

it('merges menu fields when addMenuFields is called multiple times with arrays', function () {
    $plugin = FilamentMenuBuilderPlugin::make();

    $plugin->addMenuFields([
        TextInput::make('field_a'),
    ]);

    $plugin->addMenuFields([
        TextInput::make('field_b'),
    ]);

    $fields = $plugin->getMenuFields();

    expect($fields)->toBeArray()
        ->and($fields)->toHaveCount(2);
});

it('merges menu item fields when addMenuItemFields is called multiple times', function () {
    $plugin = FilamentMenuBuilderPlugin::make();

    $plugin->addMenuItemFields([
        TextInput::make('field_x'),
    ]);

    $plugin->addMenuItemFields([
        TextInput::make('field_y'),
    ]);

    $fields = $plugin->getMenuItemFields();

    expect($fields)->toBeArray()
        ->and($fields)->toHaveCount(2);
});

it('replaces fields when addMenuFields is called with a closure', function () {
    $plugin = FilamentMenuBuilderPlugin::make();

    $plugin->addMenuFields([
        TextInput::make('field_a'),
    ]);

    $closure = fn () => [TextInput::make('field_b')];
    $plugin->addMenuFields($closure);

    // Closure should replace, not merge
    expect($plugin->getMenuFields())->toBe($closure);
});

// --- StaticMenuPanel::add() should support extra attributes ---

it('can add static panel items with target, icon, and classes', function () {
    $panel = StaticMenuPanel::make('Links')
        ->add('Home', '/', target: '_blank', icon: 'heroicon-o-home', classes: 'text-bold');

    $items = $panel->getItems();

    expect($items)->toHaveCount(1)
        ->and($items[0]['title'])->toBe('Home')
        ->and($items[0]['url'])->toBe('/')
        ->and($items[0]['target'])->toBe('_blank')
        ->and($items[0]['icon'])->toBe('heroicon-o-home')
        ->and($items[0]['classes'])->toBe('text-bold');
});

it('defaults extra attributes to null when not provided', function () {
    $panel = StaticMenuPanel::make('Links')
        ->add('About', '/about');

    $items = $panel->getItems();

    expect($items[0])->not->toHaveKey('target')
        ->and($items[0])->not->toHaveKey('icon')
        ->and($items[0])->not->toHaveKey('classes');
});

// --- Misleading event name: menu:created → menu:changed ---

it('dispatches menu:changed event when creating custom link', function () {
    $menu = Menu::create(['name' => 'Test']);

    livewire(CreateCustomLink::class, ['menu' => $menu])
        ->fillForm([
            'title' => 'Link',
            'url' => '/link',
            'target' => '_self',
        ])
        ->call('save')
        ->assertDispatched('menu:changed');
});

it('dispatches menu:changed event when creating custom text', function () {
    $menu = Menu::create(['name' => 'Test']);

    livewire(CreateCustomText::class, ['menu' => $menu])
        ->fillForm(['title' => 'Text'])
        ->call('save')
        ->assertDispatched('menu:changed');
});

it('dispatches menu:changed event when adding panel items', function () {
    $menu = Menu::create(['name' => 'Test']);
    $panel = StaticMenuPanel::make('Links')->add('Home', '/');

    livewire(MenuPanel::class, ['menu' => $menu, 'menuPanel' => $panel])
        ->set('data', ['Home'])
        ->call('add')
        ->assertDispatched('menu:changed');
});

// --- TranslatableFieldWrapper primaryLocale parameter ---

it('uses custom primary locale for required field', function () {
    $source = file_get_contents(__DIR__ . '/../src/Support/TranslatableFieldWrapper.php');

    // Should accept an optional primaryLocale parameter
    expect($source)->toContain('?string $primaryLocale = null');
});
