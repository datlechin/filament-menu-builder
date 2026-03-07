<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Resources\MenuResource\Pages;

use Datlechin\FilamentMenuBuilder\Concerns\HasLocationAction;
use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditMenu extends EditRecord
{
    use HasLocationAction;

    public static function getResource(): string
    {
        return FilamentMenuBuilderPlugin::get()->getResource();
    }

    public function content(Schema $schema): Schema
    {
        $plugin = FilamentMenuBuilderPlugin::get();

        $panelComponents = [];

        foreach ($plugin->getMenuPanels() as $index => $menuPanel) {
            $panelComponents[] = Livewire::make('menu-builder-panel', [
                'menu' => $this->getRecord(),
                'menuPanel' => $menuPanel,
            ])->key("menu-panel-{$index}");
        }

        if ($plugin->isShowCustomLinkPanel()) {
            $panelComponents[] = Livewire::make('create-custom-link', [
                'menu' => $this->getRecord(),
            ])->key('create-custom-link');
        }

        if ($plugin->isShowCustomTextPanel()) {
            $panelComponents[] = Livewire::make('create-custom-text', [
                'menu' => $this->getRecord(),
            ])->key('create-custom-text');
        }

        return $schema
            ->components([
                $this->getFormContentComponent(),
                Grid::make([
                    'default' => 1,
                    'sm' => 3,
                ])->schema([
                    Group::make($panelComponents)
                        ->columnSpan([
                            'default' => 1,
                            'sm' => 1,
                        ]),
                    Section::make()
                        ->schema([
                            Livewire::make('menu-builder-items', [
                                'menu' => $this->getRecord(),
                            ])->key('menu-builder-items'),
                        ])
                        ->columnSpan([
                            'default' => 1,
                            'sm' => 2,
                        ]),
                ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            $this->getLocationAction(),
        ];
    }
}
