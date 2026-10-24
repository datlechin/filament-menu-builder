<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\ItemTypes;

use Datlechin\FilamentMenuBuilder\Rules\MenuUrl;
use Datlechin\FilamentMenuBuilder\Support\TranslatableField;
use Filament\Forms\Components\TextInput;

class LinkItemType extends ItemType
{
    public static function make(): static
    {
        return new static;
    }

    public function getName(): string
    {
        return 'link';
    }

    protected function getDefaultLabel(): string
    {
        return __('filament-menu-builder::menu-builder.item_types.link');
    }

    protected function getDefaultIcon(): string
    {
        return 'heroicon-o-link';
    }

    public function getDestinationSchema(): array
    {
        return [
            TranslatableField::make('menu_item', 'url', fn (string $statePath): TextInput => TextInput::make($statePath)
                ->label(__('filament-menu-builder::menu-builder.form.url'))
                ->placeholder('https://example.com')
                ->required()
                ->maxLength(2048)
                ->rule(new MenuUrl)),
        ];
    }
}
