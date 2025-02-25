<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Livewire;

use Datlechin\FilamentMenuBuilder\Enums\LinkTarget;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;
use SolutionForest\FilamentTranslateField\Forms\Component\Translate;

class CreateCustomLink extends Component implements HasForms
{
    use InteractsWithForms;

    public Menu $menu;

    public array|string $title;

    public string $url = '';

    public string $target = LinkTarget::Self->value;

    public function save(): void
    {
        $this->validate([
            'title' => ['required'],
            'url' => ['required', 'string'],
            'target' => ['required', 'string', Rule::in(LinkTarget::cases())],
        ]);

        $this->menu
            ->menuItems()
            ->create([
                'title' => $this->title,
                'url' => $this->url,
                'target' => $this->target,
                'order' => $this->menu->menuItems->max('order') + 1,
            ]);

        Notification::make()
            ->title(__('filament-menu-builder::menu-builder.notifications.created.title'))
            ->success()
            ->send();

        $this->reset('title', 'url', 'target');
        $this->dispatch('menu:created');
    }

    public function form(Form $form): Form
    {


        return $form
            ->schema([
                config('filament-menu-builder.translation')?
                    Translate::make()
                        ->locales(config('filament-menu-builder.locales'))
                        ->schema([
                            TextInput::make("title")->required(),
                        ]) : TextInput::make("title")->required()
                ,
                TextInput::make('url')
                    ->label(__('filament-menu-builder::menu-builder.form.url'))
                    ->required(),
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
