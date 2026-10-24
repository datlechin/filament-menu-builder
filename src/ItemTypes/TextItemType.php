<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\ItemTypes;

class TextItemType extends ItemType
{
    public static function make(): static
    {
        return new static;
    }

    public function getName(): string
    {
        return 'text';
    }

    protected function getDefaultLabel(): string
    {
        return __('filament-menu-builder::menu-builder.item_types.text');
    }

    protected function getDefaultIcon(): string
    {
        return 'heroicon-o-bars-3-bottom-left';
    }

    public function opensLink(): bool
    {
        return false;
    }
}
