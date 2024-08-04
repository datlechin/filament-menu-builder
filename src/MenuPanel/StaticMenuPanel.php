<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\MenuPanel;

use Closure;

class StaticMenuPanel extends AbstractMenuPanel
{
    protected string $name = 'Static Menu';

    protected array $items = [];

    public function __construct(?string $name = null)
    {
        if ($name) {
            $this->name = $name;
        }
    }

    public static function make(?string $name = null): static
    {
        return new static($name);
    }

    public function add(string $title, Closure | string $url): static
    {
        $this->items[] = [
            'title' => $title,
            'url' => $url,
        ];

        return $this;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
