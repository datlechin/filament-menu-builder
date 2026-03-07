<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Livewire;

use Datlechin\FilamentMenuBuilder\Enums\LinkTarget;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CreateCustomLink extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public Menu $menu;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'target' => LinkTarget::Self->value,
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $this->menu
            ->menuItems()
            ->create([
                'title' => $state['title'],
                'url' => $state['url'],
                'icon' => $state['icon'] ?? null,
                'classes' => $state['classes'] ?? null,
                'target' => $state['target'],
                'order' => $this->menu->menuItems->max('order') + 1,
            ]);

        Notification::make()
            ->title(__('filament-menu-builder::menu-builder.notifications.created.title'))
            ->success()
            ->send();

        $this->form->fill([
            'target' => LinkTarget::Self->value,
        ]);
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
                TextInput::make('url')
                    ->label(__('filament-menu-builder::menu-builder.form.url'))
                    ->required(),
                TextInput::make('icon')
                    ->label(__('filament-menu-builder::menu-builder.form.icon'))
                    ->placeholder('heroicon-o-home'),
                TextInput::make('classes')
                    ->label(__('filament-menu-builder::menu-builder.form.classes'))
                    ->placeholder('text-sm font-bold'),
                Select::make('target')
                    ->label(__('filament-menu-builder::menu-builder.open_in.label'))
                    ->options(LinkTarget::class)
                    ->default(LinkTarget::Self),
            ]);
    }

    public function render(): View
    {
        return view('filament-menu-builder::livewire.create-custom-link');
    }
}
