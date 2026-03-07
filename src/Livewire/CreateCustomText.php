<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Livewire;

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Support\TranslatableFieldWrapper;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CreateCustomText extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public Menu $menu;

    public ?array $data = [];

    public function save(): void
    {
        $state = $this->form->getState();

        DB::transaction(function () use ($state) {
            $order = ($this->menu->menuItems()->lockForUpdate()->max('order') ?? 0) + 1;

            $this->menu
                ->menuItems()
                ->create([
                    'title' => $state['title'],
                    'order' => $order,
                ]);
        });

        Notification::make()
            ->title(__('filament-menu-builder::menu-builder.notifications.created.title'))
            ->success()
            ->send();

        $this->form->fill();
        $this->dispatch('menu:changed');
    }

    public function form(Schema $schema): Schema
    {
        $plugin = FilamentMenuBuilderPlugin::get();

        $titleField = TextInput::make('title')
            ->label(__('filament-menu-builder::menu-builder.form.title'))
            ->required();

        if ($plugin->isTranslatable() && in_array('title', $plugin->getTranslatableMenuItemFields())) {
            $titleField = TranslatableFieldWrapper::wrap($titleField, $plugin->getTranslatableLocales());
        }

        return $schema
            ->statePath('data')
            ->components([
                $titleField,
            ]);
    }

    public function render(): View
    {
        return view('filament-menu-builder::livewire.create-custom-text');
    }
}
