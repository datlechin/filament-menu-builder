<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Contracts;

interface MenuPanel
{
    public function getIdentifier(): string;

    public function getName(): string;

    public function getItems(): array;

    public function getSort(): int;
}
