<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\MenuPanel;

use Datlechin\FilamentMenuBuilder\Contracts\MenuPanel;

abstract class AbstractMenuPanel implements MenuPanel
{
    protected string $name;

    protected int $sort = 999;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function make(string $name = 'Static Menu'): static
    {
        return new static($name);
    }

    public function getIdentifier(): string
    {
        return str($this->getName())
            ->slug()
            ->toString();
    }

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
