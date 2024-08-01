<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Livewire;

use Datlechin\FilamentMenuBuilder\Models\Menu;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CreateCustomLink extends Component implements HasForms
{
    use InteractsWithForms;

    public Menu $menu;

    public array $data = [];

    public function save(): void
    {
        $this->validate([
            'data.title' => ['required', 'string'],
            'data.url' => ['required', 'string'],
            'data.is_external' => ['sometimes', 'bool'],
        ]);

        $this->menu
            ->menuItems()
            ->create([
                ...$this->data,
                'order' => $this->menu->menuItems()->max('order') + 1,
            ]);

        Notification::make()
            ->title('Đã thêm liên kết tùy chỉnh vào menu.')
            ->success()
            ->send();

        $this->reset(['data']);
        $this->dispatch('menu:created');
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                TextInput::make('title')
                    ->label('Tiêu đề')
                    ->required(),
                TextInput::make('url')
                    ->label('URL')
                    ->required(),
                Checkbox::make('is_external')
                    ->default(false)
                    ->label('Liên kết bên ngoài'),
            ]);
    }

    public function render(): View
    {
        return view('filament-menu-builder::livewire.create-custom-link');
    }
}
