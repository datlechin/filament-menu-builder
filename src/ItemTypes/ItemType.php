<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\ItemTypes;

use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Filament\Schemas\Components\Component;

abstract class ItemType
{
    protected ?string $label = null;

    protected ?string $icon = null;

    abstract public function getName(): string;

    abstract protected function getDefaultLabel(): string;

    protected function getDefaultIcon(): ?string
    {
        return null;
    }

    public function getKey(): string
    {
        return $this->getName();
    }

    public function label(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label ?? $this->getDefaultLabel();
    }

    public function icon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon ?? $this->getDefaultIcon();
    }

    public function matches(MenuItem $item): bool
    {
        return $item->type === $this->getName();
    }

    public function opensLink(): bool
    {
        return true;
    }

    /**
     * @return array<Component>
     */
    public function getDestinationSchema(): array
    {
        return [];
    }

    /**
     * @return array<Component>|null
     */
    public function getCreateSchema(): ?array
    {
        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array<string, mixed>>
     */
    public function getItemsToCreate(array $data): array
    {
        return [$data];
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return ['type' => $this->getName()];
    }
}
