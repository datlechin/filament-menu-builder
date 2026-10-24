<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\ItemTypes\ItemType;

arch('source files use strict types')
    ->expect('Datlechin\FilamentMenuBuilder')
    ->toUseStrictTypes();

arch('no debugging statements')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();

arch('contracts are interfaces')
    ->expect('Datlechin\FilamentMenuBuilder\Contracts')
    ->toBeInterfaces();

arch('concerns are traits')
    ->expect('Datlechin\FilamentMenuBuilder\Concerns')
    ->toBeTraits();

arch('item types extend the base item type')
    ->expect('Datlechin\FilamentMenuBuilder\ItemTypes')
    ->toExtend(ItemType::class)
    ->ignoring(ItemType::class);

arch('models and frontend rendering work without Filament')
    ->expect([
        'Datlechin\FilamentMenuBuilder\Models',
        'Datlechin\FilamentMenuBuilder\Concerns\HasMenuItems',
        'Datlechin\FilamentMenuBuilder\Concerns\HasTranslatableAttributes',
        'Datlechin\FilamentMenuBuilder\Support\MenuTree',
        'Datlechin\FilamentMenuBuilder\Support\MenuNode',
        'Datlechin\FilamentMenuBuilder\View',
    ])
    ->not->toUse(['Filament', 'Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin']);
