<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Support\TranslatableFieldWrapper;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;

// --- Plugin API Tests ---

it('is not translatable by default', function () {
    $plugin = FilamentMenuBuilderPlugin::make();

    expect($plugin->isTranslatable())->toBeFalse()
        ->and($plugin->getTranslatableLocales())->toBeNull();
});

it('can enable translatable with locales', function () {
    $plugin = FilamentMenuBuilderPlugin::make();
    $plugin->translatable(['en', 'nl', 'vi']);

    expect($plugin->isTranslatable())->toBeTrue()
        ->and($plugin->getTranslatableLocales())->toBe(['en', 'nl', 'vi']);
});

it('defaults translatable menu item fields to title', function () {
    $plugin = FilamentMenuBuilderPlugin::make();

    expect($plugin->getTranslatableMenuItemFields())->toBe(['title']);
});

it('defaults translatable menu fields to empty', function () {
    $plugin = FilamentMenuBuilderPlugin::make();

    expect($plugin->getTranslatableMenuFields())->toBe([]);
});

it('can set translatable menu item fields', function () {
    $plugin = FilamentMenuBuilderPlugin::make();
    $plugin->translatableMenuItemFields(['title', 'url']);

    expect($plugin->getTranslatableMenuItemFields())->toBe(['title', 'url']);
});

it('can set translatable menu fields', function () {
    $plugin = FilamentMenuBuilderPlugin::make();
    $plugin->translatableMenuFields(['name']);

    expect($plugin->getTranslatableMenuFields())->toBe(['name']);
});

// --- TranslatableFieldWrapper Tests ---

it('wraps a field in locale tabs', function () {
    $field = TextInput::make('title')->required();
    $result = TranslatableFieldWrapper::wrap($field, ['en', 'nl']);

    expect($result)->toBeInstanceOf(Tabs::class);
});

// --- MenuItem resolveLocale Tests ---

it('resolves string value from resolveLocale', function () {
    $item = new MenuItem;

    expect($item->resolveLocale('Home'))->toBe('Home');
});

it('resolves array value for current locale from resolveLocale', function () {
    $item = new MenuItem;

    app()->setLocale('en');
    expect($item->resolveLocale(['en' => 'Home', 'nl' => 'Thuis']))->toBe('Home');

    app()->setLocale('nl');
    expect($item->resolveLocale(['en' => 'Home', 'nl' => 'Thuis']))->toBe('Thuis');
});

it('falls back to first locale when current locale is missing', function () {
    $item = new MenuItem;

    app()->setLocale('fr');
    expect($item->resolveLocale(['en' => 'Home', 'nl' => 'Thuis']))->toBe('Home');
});

it('resolves null value as empty string', function () {
    $item = new MenuItem;

    expect($item->resolveLocale(null))->toBe('');
});

it('resolves empty array as empty string', function () {
    $item = new MenuItem;

    expect($item->resolveLocale([]))->toBe('');
});

// --- Menu resolveLocale Tests ---

it('resolves locale on Menu model', function () {
    $menu = new Menu;

    app()->setLocale('en');
    expect($menu->resolveLocale(['en' => 'Main', 'nl' => 'Hoofd']))->toBe('Main');
    expect($menu->resolveLocale('Main Menu'))->toBe('Main Menu');
});
