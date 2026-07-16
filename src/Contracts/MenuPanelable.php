<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Contracts;

interface MenuPanelable
{
    public function getMenuPanelName(): string;

    public function getMenuPanelTitle(): string;

    public function getMenuPanelUrl(): string;
}
