<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Tests\Fixtures;

class ReadOnlyMenuPolicy
{
    public function viewAny(): bool
    {
        return true;
    }

    public function view(): bool
    {
        return true;
    }

    public function update(): bool
    {
        return false;
    }
}
