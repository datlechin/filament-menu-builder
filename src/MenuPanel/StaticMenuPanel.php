<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\MenuPanel;

use Closure;

class StaticMenuPanel extends AbstractMenuPanel
{
    protected array $items = [];

    public function add(
        string $title,
        Closure | string $url,
        ?string $target = null,
        ?string $icon = null,
        ?string $classes = null,
    ): static {
        $item = [
            'title' => $title,
            'url' => $url,
        ];

        if ($target !== null) {
            $item['target'] = $target;
        }

        if ($icon !== null) {
            $item['icon'] = $icon;
        }

        if ($classes !== null) {
            $item['classes'] = $classes;
        }

        $this->items[] = $item;

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
