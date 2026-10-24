<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Contracts;

interface MenuLinkable
{
    public function getMenuTitle(): string;

    public function getMenuUrl(): string;
}
