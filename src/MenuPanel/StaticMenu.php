<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder;

class StaticMenu
{
    protected array $items = [];

    public static function make(): static
    {
        return new static;
    }

    public function add(string $title, \Closure | string $url): static
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
}
