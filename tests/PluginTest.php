<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Contracts\MenuPanel as MenuPanelContract;
use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\MenuPanel\StaticMenuPanel;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Models\MenuLocation;
use Datlechin\FilamentMenuBuilder\Resources\MenuResource;
use Filament\Forms\Components\TextInput;

it('has an id', function () {
    $plugin = FilamentMenuBuilderPlugin::make();

    expect($plugin->getId())->toBe('menu-builder');
});

it('can be retrieved via filament helper', function () {
    $plugin = FilamentMenuBuilderPlugin::get();

    expect($plugin)->toBeInstanceOf(FilamentMenuBuilderPlugin::class);
});

it('returns default resource class', function () {
    $plugin = FilamentMenuBuilderPlugin::make();

    expect($plugin->getResource())->toBe(MenuResource::class);
});

it('can set custom resource', function () {
    $plugin = FilamentMenuBuilderPlugin::make();
    $plugin->usingResource('App\\CustomResource');

    expect($plugin->getResource())->toBe('App\\CustomResource');
});

it('returns default model classes', function () {
    $plugin = FilamentMenuBuilderPlugin::make();

    expect($plugin->getMenuModel())->toBe(Menu::class)
        ->and($plugin->getMenuItemModel())->toBe(MenuItem::class)
        ->and($plugin->getMenuLocationModel())->toBe(MenuLocation::class);
});

it('can set custom model classes', function () {
    $plugin = FilamentMenuBuilderPlugin::make();
    $plugin->usingMenuModel('App\\CustomMenu');
    $plugin->usingMenuItemModel('App\\CustomMenuItem');
    $plugin->usingMenuLocationModel('App\\CustomMenuLocation');

    expect($plugin->getMenuModel())->toBe('App\\CustomMenu')
        ->and($plugin->getMenuItemModel())->toBe('App\\CustomMenuItem')
        ->and($plugin->getMenuLocationModel())->toBe('App\\CustomMenuLocation');
});

it('can add a single location', function () {
    $plugin = FilamentMenuBuilderPlugin::make();
    $plugin->addLocation('header', 'Header');

    expect($plugin->getLocations())->toBe(['header' => 'Header']);
});

it('can add multiple locations', function () {
    $plugin = FilamentMenuBuilderPlugin::make();
    $plugin->addLocations([
        'header' => 'Header',
        'footer' => 'Footer',
    ]);

    expect($plugin->getLocations())->toBe([
        'header' => 'Header',
        'footer' => 'Footer',
    ]);
});

it('can add a menu panel', function () {
    $plugin = FilamentMenuBuilderPlugin::make();
    $panel = StaticMenuPanel::make('Test Panel');
    $plugin->addMenuPanel($panel);

    expect($plugin->getMenuPanels())->toHaveCount(1)
        ->and($plugin->getMenuPanels()[0])->toBeInstanceOf(MenuPanelContract::class);
});

it('can add multiple menu panels', function () {
    $plugin = FilamentMenuBuilderPlugin::make();
    $plugin->addMenuPanels([
        StaticMenuPanel::make('Panel 1'),
        StaticMenuPanel::make('Panel 2'),
    ]);

    expect($plugin->getMenuPanels())->toHaveCount(2);
});

it('sorts menu panels by sort order', function () {
    $plugin = FilamentMenuBuilderPlugin::make();
    $plugin->addMenuPanels([
        StaticMenuPanel::make('Panel B')->sort(2),
        StaticMenuPanel::make('Panel A')->sort(1),
    ]);

    $panels = array_values($plugin->getMenuPanels());

    expect($panels[0]->getName())->toBe('Panel A')
        ->and($panels[1]->getName())->toBe('Panel B');
});

it('shows custom link panel by default', function () {
    $plugin = FilamentMenuBuilderPlugin::make();

    expect($plugin->isShowCustomLinkPanel())->toBeTrue();
});

it('can hide custom link panel', function () {
    $plugin = FilamentMenuBuilderPlugin::make();
    $plugin->showCustomLinkPanel(false);

    expect($plugin->isShowCustomLinkPanel())->toBeFalse();
});

it('hides custom text panel by default', function () {
    $plugin = FilamentMenuBuilderPlugin::make();

    expect($plugin->isShowCustomTextPanel())->toBeFalse();
});

it('can show custom text panel', function () {
    $plugin = FilamentMenuBuilderPlugin::make();
    $plugin->showCustomTextPanel(true);

    expect($plugin->isShowCustomTextPanel())->toBeTrue();
});

it('enables indent actions by default', function () {
    $plugin = FilamentMenuBuilderPlugin::make();

    expect($plugin->isIndentActionsEnabled())->toBeTrue();
});

it('can disable indent actions', function () {
    $plugin = FilamentMenuBuilderPlugin::make();
    $plugin->enableIndentActions(false);

    expect($plugin->isIndentActionsEnabled())->toBeFalse();
});

it('returns default navigation icon', function () {
    $plugin = FilamentMenuBuilderPlugin::make();

    expect($plugin->getNavigationIcon())->toBe('heroicon-o-bars-3');
});

it('can set navigation icon', function () {
    $plugin = FilamentMenuBuilderPlugin::make();
    $plugin->navigationIcon('heroicon-o-menu');

    expect($plugin->getNavigationIcon())->toBe('heroicon-o-menu');
});

it('returns null navigation label by default', function () {
    $plugin = FilamentMenuBuilderPlugin::make();

    expect($plugin->getNavigationLabel())->toBeNull();
});

it('can set navigation label', function () {
    $plugin = FilamentMenuBuilderPlugin::make();
    $plugin->navigationLabel('Menus');

    expect($plugin->getNavigationLabel())->toBe('Menus');
});

it('returns null navigation group by default', function () {
    $plugin = FilamentMenuBuilderPlugin::make();

    expect($plugin->getNavigationGroup())->toBeNull();
});

it('can set navigation group', function () {
    $plugin = FilamentMenuBuilderPlugin::make();
    $plugin->navigationGroup('Content');

    expect($plugin->getNavigationGroup())->toBe('Content');
});

it('returns null navigation sort by default', function () {
    $plugin = FilamentMenuBuilderPlugin::make();

    expect($plugin->getNavigationSort())->toBeNull();
});

it('can set navigation sort', function () {
    $plugin = FilamentMenuBuilderPlugin::make();
    $plugin->navigationSort(5);

    expect($plugin->getNavigationSort())->toBe(5);
});

it('does not show navigation count badge by default', function () {
    $plugin = FilamentMenuBuilderPlugin::make();

    expect($plugin->getNavigationCountBadge())->toBeFalse();
});

it('can enable navigation count badge', function () {
    $plugin = FilamentMenuBuilderPlugin::make();
    $plugin->navigationCountBadge(true);

    expect($plugin->getNavigationCountBadge())->toBeTrue();
});

it('returns empty menu fields by default', function () {
    $plugin = FilamentMenuBuilderPlugin::make();

    expect($plugin->getMenuFields())->toBe([]);
});

it('returns empty menu item fields by default', function () {
    $plugin = FilamentMenuBuilderPlugin::make();

    expect($plugin->getMenuItemFields())->toBe([]);
});

it('can set menu fields', function () {
    $plugin = FilamentMenuBuilderPlugin::make();
    $plugin->addMenuFields([
        TextInput::make('custom_field'),
    ]);

    expect($plugin->getMenuFields())->toHaveCount(1);
});

it('can set menu item fields', function () {
    $plugin = FilamentMenuBuilderPlugin::make();
    $plugin->addMenuItemFields([
        TextInput::make('custom_field'),
    ]);

    expect($plugin->getMenuItemFields())->toHaveCount(1);
});

it('accepts closure for menu fields', function () {
    $plugin = FilamentMenuBuilderPlugin::make();
    $closure = fn () => [];
    $plugin->addMenuFields($closure);

    expect($plugin->getMenuFields())->toBe($closure);
});
