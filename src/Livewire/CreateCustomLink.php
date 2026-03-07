<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Livewire;

use Datlechin\FilamentMenuBuilder\Enums\LinkTarget;
use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Support\TranslatableFieldWrapper;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
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

        DB::transaction(function () use ($state) {
            $order = ($this->menu->menuItems()->lockForUpdate()->max('order') ?? 0) + 1;

            $this->menu
                ->menuItems()
                ->create([
                    'title' => $state['title'],
                    'url' => $state['url'],
                    'icon' => $state['icon'] ?? null,
                    'classes' => $state['classes'] ?? null,
                    'rel' => $state['rel'] ?? null,
                    'target' => $state['target'],
                    'order' => $order,
                ]);
        });

        Notification::make()
            ->title(__('filament-menu-builder::menu-builder.notifications.created.title'))
            ->success()
            ->send();

        $this->form->fill([
            'target' => LinkTarget::Self->value,
        ]);
        $this->dispatch('menu:changed');
    }

    public function form(Schema $schema): Schema
    {
        $plugin = FilamentMenuBuilderPlugin::get();

        $titleField = TextInput::make('title')
            ->label(__('filament-menu-builder::menu-builder.form.title'))
            ->required();

        $urlField = TextInput::make('url')
            ->label(__('filament-menu-builder::menu-builder.form.url'))
            ->required();

        if ($plugin->isTranslatable()) {
            $locales = $plugin->getTranslatableLocales();

            if (in_array('title', $plugin->getTranslatableMenuItemFields())) {
                $titleField = TranslatableFieldWrapper::wrap($titleField, $locales);
            }

            if (in_array('url', $plugin->getTranslatableMenuItemFields())) {
                $urlField = TranslatableFieldWrapper::wrap($urlField, $locales);
            }
        }

        return $schema
            ->statePath('data')
            ->components([
                $titleField,
                $urlField,
                TextInput::make('icon')
                    ->label(__('filament-menu-builder::menu-builder.form.icon'))
                    ->placeholder('heroicon-o-home'),
                TextInput::make('classes')
                    ->label(__('filament-menu-builder::menu-builder.form.classes'))
                    ->placeholder('text-sm font-bold'),
                TextInput::make('rel')
                    ->label(__('filament-menu-builder::menu-builder.form.rel'))
                    ->placeholder('nofollow noopener noreferrer'),
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
