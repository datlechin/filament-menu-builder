<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Contracts\MenuPanel as MenuPanelContract;
use Datlechin\FilamentMenuBuilder\MenuPanel\StaticMenuPanel;

it('implements MenuPanel contract', function () {
    $panel = StaticMenuPanel::make('Test');

    expect($panel)->toBeInstanceOf(MenuPanelContract::class);
});

it('can be created with a name', function () {
    $panel = StaticMenuPanel::make('Test Panel');

    expect($panel->getName())->toBe('Test Panel');
});

it('generates a slug identifier', function () {
    $panel = StaticMenuPanel::make('My Test Panel');

    expect($panel->getIdentifier())->toBe('my-test-panel');
});

it('can add a static item', function () {
    $panel = StaticMenuPanel::make('Links')
        ->add('Home', '/');

    expect($panel->getItems())->toHaveCount(1)
        ->and($panel->getItems()[0])->toBe(['title' => 'Home', 'url' => '/']);
});

it('can add multiple items', function () {
    $panel = StaticMenuPanel::make('Links')
        ->add('Home', '/')
        ->add('About', '/about');

    expect($panel->getItems())->toHaveCount(2);
});

it('can add many items at once', function () {
    $panel = StaticMenuPanel::make('Links')
        ->addMany([
            'Home' => '/',
            'About' => '/about',
            'Contact' => '/contact',
        ]);

    expect($panel->getItems())->toHaveCount(3);
});

it('supports closure urls', function () {
    $panel = StaticMenuPanel::make('Links')
        ->add('Home', fn () => '/dynamic-url');

    $items = $panel->getItems();

    expect($items[0]['url'])->toBeInstanceOf(Closure::class)
        ->and(($items[0]['url'])())->toBe('/dynamic-url');
});

it('has default sort of 999', function () {
    $panel = StaticMenuPanel::make('Test');

    expect($panel->getSort())->toBe(999);
});

it('can set sort order', function () {
    $panel = StaticMenuPanel::make('Test')->sort(5);

    expect($panel->getSort())->toBe(5);
});

it('has null description by default', function () {
    $panel = StaticMenuPanel::make('Test');

    expect($panel->getDescription())->toBeNull();
});

it('can set description', function () {
    $panel = StaticMenuPanel::make('Test')->description('A panel');

    expect($panel->getDescription())->toBe('A panel');
});

it('has null icon by default', function () {
    $panel = StaticMenuPanel::make('Test');

    expect($panel->getIcon())->toBeNull();
});

it('can set icon', function () {
    $panel = StaticMenuPanel::make('Test')->icon('heroicon-o-link');

    expect($panel->getIcon())->toBe('heroicon-o-link');
});

it('is collapsible by default', function () {
    $panel = StaticMenuPanel::make('Test');

    expect($panel->isCollapsible())->toBeTrue();
});

it('can disable collapsibility', function () {
    $panel = StaticMenuPanel::make('Test')->collapsible(false);

    expect($panel->isCollapsible())->toBeFalse();
});

it('is not collapsed by default', function () {
    $panel = StaticMenuPanel::make('Test');

    expect($panel->isCollapsed())->toBeFalse();
});

it('can be collapsed', function () {
    $panel = StaticMenuPanel::make('Test')->collapsed();

    expect($panel->isCollapsed())->toBeTrue();
});

it('is not paginated by default', function () {
    $panel = StaticMenuPanel::make('Test');

    expect($panel->isPaginated())->toBeFalse();
});

it('can enable pagination', function () {
    $panel = StaticMenuPanel::make('Test')->paginate(10);

    expect($panel->isPaginated())->toBeTrue()
        ->and($panel->getPerPage())->toBe(10);
});

it('has default per page of 5', function () {
    $panel = StaticMenuPanel::make('Test');

    expect($panel->getPerPage())->toBe(5);
});

it('can disable pagination via condition', function () {
    $panel = StaticMenuPanel::make('Test')->paginate(10, false);

    expect($panel->isPaginated())->toBeFalse();
});
