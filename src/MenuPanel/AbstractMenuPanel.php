<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\MenuPanel;

use Datlechin\FilamentMenuBuilder\Contracts\MenuPanel;

abstract class AbstractMenuPanel implements MenuPanel
{
    protected int $sort = 0;

    public function sort(int $sort): static
    {
        $this->sort = $sort;

        return $this;
    }

    public function getSort(): int
    {
        return $this->sort;
    }
}
