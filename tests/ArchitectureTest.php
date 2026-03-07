<?php

declare(strict_types=1);

arch('models extend Eloquent Model')
    ->expect('Datlechin\FilamentMenuBuilder\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model');

arch('enums are backed enums')
    ->expect('Datlechin\FilamentMenuBuilder\Enums')
    ->toBeEnums();

arch('contracts are interfaces')
    ->expect('Datlechin\FilamentMenuBuilder\Contracts')
    ->toBeInterfaces();

arch('concerns are traits')
    ->expect('Datlechin\FilamentMenuBuilder\Concerns')
    ->toBeTraits();

arch('plugin implements Filament Plugin interface')
    ->expect('Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin')
    ->toImplement('Filament\Contracts\Plugin');

arch('livewire components extend Livewire Component')
    ->expect('Datlechin\FilamentMenuBuilder\Livewire')
    ->toExtend('Livewire\Component');

arch('menu panel classes are abstract or concrete')
    ->expect('Datlechin\FilamentMenuBuilder\MenuPanel')
    ->toImplement('Datlechin\FilamentMenuBuilder\Contracts\MenuPanel');

arch('no debugging statements')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed();

arch('strict types in all source files')
    ->expect('Datlechin\FilamentMenuBuilder')
    ->toUseStrictTypes();
