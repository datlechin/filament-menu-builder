<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Contracts;

interface MenuPanel
{
    public function getIdentifier(): string;

    public function getName(): string;

    public function getItems(): array;

    public function getSort(): int;

    public function getDescription(): ?string;

    public function getIcon(): ?string;

    public function isCollapsible(): bool;

    public function isCollapsed(): bool;

    public function isPaginated(): bool;

    public function getPerPage(): int;
}
