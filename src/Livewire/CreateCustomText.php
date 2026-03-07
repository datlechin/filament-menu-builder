<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Livewire;

use Datlechin\FilamentMenuBuilder\Models\Menu;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CreateCustomText extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public Menu $menu;

    public ?array $data = [];

    public function save(): void
    {
        $state = $this->form->getState();

        $this->menu
            ->menuItems()
            ->create([
                'title' => $state['title'],
                'order' => $this->menu->menuItems->max('order') + 1,
            ]);

        Notification::make()
            ->title(__('filament-menu-builder::menu-builder.notifications.created.title'))
            ->success()
            ->send();

        $this->form->fill();
        $this->dispatch('menu:created');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('title')
                    ->label(__('filament-menu-builder::menu-builder.form.title'))
                    ->required(),
            ]);
    }

    public function render(): View
    {
        return view('filament-menu-builder::livewire.create-custom-text');
    }
}
