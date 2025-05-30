<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;

it('can enable indent actions', function () {
    $plugin = FilamentMenuBuilderPlugin::make()
        ->enableIndentActions();

    expect($plugin->isIndentActionsEnabled())->toBeTrue();
});

it('can disable indent actions', function () {
    $plugin = FilamentMenuBuilderPlugin::make()
        ->enableIndentActions(false);

    expect($plugin->isIndentActionsEnabled())->toBeFalse();
});

it('has indent actions enabled by default', function () {
    $plugin = FilamentMenuBuilderPlugin::make();

    expect($plugin->isIndentActionsEnabled())->toBeTrue();
});

it('can configure custom link panel', function () {
    $plugin = FilamentMenuBuilderPlugin::make()
        ->showCustomLinkPanel(false);

    expect($plugin->isShowCustomLinkPanel())->toBeFalse();
});

it('can configure custom text panel', function () {
    $plugin = FilamentMenuBuilderPlugin::make()
        ->showCustomTextPanel(true);

    expect($plugin->isShowCustomTextPanel())->toBeTrue();
});

it('can add locations', function () {
    $plugin = FilamentMenuBuilderPlugin::make()
        ->addLocation('header', 'Header')
        ->addLocation('footer', 'Footer');

    $locations = $plugin->getLocations();

    expect($locations)->toHaveKey('header')
        ->and($locations['header'])->toBe('Header')
        ->and($locations)->toHaveKey('footer')
        ->and($locations['footer'])->toBe('Footer');
});

it('can add multiple locations at once', function () {
    $plugin = FilamentMenuBuilderPlugin::make()
        ->addLocations([
            'primary' => 'Primary Navigation',
            'secondary' => 'Secondary Navigation',
        ]);

    $locations = $plugin->getLocations();

    expect($locations)->toHaveKey('primary')
        ->and($locations['primary'])->toBe('Primary Navigation')
        ->and($locations)->toHaveKey('secondary')
        ->and($locations['secondary'])->toBe('Secondary Navigation');
});
