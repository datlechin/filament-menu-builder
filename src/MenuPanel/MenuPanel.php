<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder;

class MenuPanel
{
    protected string $heading;

    protected array $items = [];

    public static function make(): static
    {
        return new static;
    }

    public function heading(string $heading): static
    {
        $this->heading = $heading;

        return $this;
    }

    public function addItem(string $title, \Closure | string $url): static
    {
        $this->items[] = [
            'title' => $title,
            'url' => $url,
        ];

        return $this;
    }

    public function addItems(array $items): static
    {
        foreach ($items as $item) {
            $this->addItem($item['title'], $item['url']);
        }

        return $this;
    }

    public function getHeading(): string
    {
        return $this->heading;
    }

    public function getItems(): array
    {
        return $this->items;
    }
}
