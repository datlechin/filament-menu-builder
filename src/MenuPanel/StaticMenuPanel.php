<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\MenuPanel;

use Closure;

class StaticMenuPanel extends AbstractMenuPanel
{
    protected array $items = [];

    public function add(string $title, Closure | string $url): static
    {
        $this->items[] = [
            'title' => $title,
            'url' => $url,
        ];

        return $this;
    }

    public function addMany(array $items): static
    {
        foreach ($items as $title => $url) {
            $this->add($title, $url);
        }

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
