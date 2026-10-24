<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Support;

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\ItemTypes\ItemType;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;

final class MenuItemForm
{
    /**
     * @return array<Component>
     */
    public static function schema(ItemType $type): array
    {
        $opensLink = $type->opensLink();

        return [
            TranslatableField::make('menu_item', 'title', fn (string $statePath): TextInput => TextInput::make($statePath)
                ->label(__('filament-menu-builder::menu-builder.form.title'))
                ->required()
                ->maxLength(255)),
            ...$type->getDestinationSchema(),
            Toggle::make('target')
                ->label(__('filament-menu-builder::menu-builder.form.open_in_new_tab'))
                ->visible($opensLink)
                ->formatStateUsing(fn (?string $state): bool => $state === '_blank')
                ->dehydrateStateUsing(fn (?bool $state): ?string => $state ? '_blank' : null),
            Section::make(__('filament-menu-builder::menu-builder.form.advanced'))
                ->collapsed()
                ->compact()
                ->schema([
                    TextInput::make('rel')
                        ->label(__('filament-menu-builder::menu-builder.form.rel'))
                        ->placeholder('nofollow noopener')
                        ->visible($opensLink),
                    TextInput::make('icon')
                        ->label(__('filament-menu-builder::menu-builder.form.icon'))
                        ->placeholder('heroicon-o-home'),
                    TextInput::make('classes')
                        ->label(__('filament-menu-builder::menu-builder.form.classes')),
                ]),
            ...FilamentMenuBuilderPlugin::get()->getMenuItemFields($type),
            Toggle::make('is_visible')
                ->label(__('filament-menu-builder::menu-builder.form.is_visible'))
                ->default(true),
        ];
    }
}
