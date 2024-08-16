<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\MenuPanel;

use Datlechin\FilamentMenuBuilder\Contracts\MenuPanel;

abstract class AbstractMenuPanel implements MenuPanel
{
    protected string $name;

    protected int $sort = 999;

    protected ?string $description = null;

    protected ?string $icon = null;

    protected bool $collapsible = true;

    protected bool $collapsed = false;

    protected bool $paginated = false;

    protected int $perPage = 5;

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

    public function description(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function collapsible(bool $collapsible = true): static
    {
        $this->collapsible = $collapsible;

        return $this;
    }

    public function isCollapsible(): bool
    {
        return $this->collapsible;
    }

    public function collapsed(bool $collapsed = true): static
    {
        $this->collapsed = $collapsed;

        return $this;
    }

    public function isCollapsed(): bool
    {
        return $this->collapsed;
    }

    public function paginate(int $perPage = 5, bool $condition = true): static
    {
        $this->perPage = $perPage;
        $this->paginated = $condition;

        return $this;
    }

    public function isPaginated(): bool
    {
        return $this->paginated;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }
}
