<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Datlechin\FilamentMenuBuilder\FilamentMenuBuilder
 */
class FilamentMenuBuilder extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Datlechin\FilamentMenuBuilder\FilamentMenuBuilder::class;
    }
}
