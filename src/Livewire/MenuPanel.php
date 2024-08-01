<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Livewire;

use Datlechin\FilamentMenuBuilder\Models\Menu;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

class MenuPanel extends Component implements HasForms
{
    use InteractsWithForms;

    public Menu $menu;

    public string $heading;

    public array $items = [];

    #[Validate('required|array')]
    public array $data = [];

    public function mount(\Datlechin\FilamentMenuBuilder\MenuPanel $menuPanel): void
    {
        $this->heading = $menuPanel->getHeading();
        $this->items = $menuPanel->getItems();
    }

    public function add(): void
    {
        $this->validate();

        $order = $this->menu->menuItems()->max('order') ?? 0;

        $selectedItems = collect($this->items)
            ->filter(fn($item) => in_array($item['title'], $this->data))
            ->map(function ($item) use (&$order) {
                return [
                    ...$item,
                    'order' => ++$order,
                ];
            });

        if ($selectedItems->isEmpty()) {
            return;
        }

        $this->menu->menuItems()->createMany($selectedItems);

        $this->reset('data');
        $this->dispatch('menu:created');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                CheckboxList::make('data')
                    ->hiddenLabel()
                    ->required()
                    ->bulkToggleable()
                    ->options(collect($this->items)->mapWithKeys(fn($item) => [$item['title'] => $item['title']])),
            ]);
    }

    public function render(): View
    {
        return view('filament-menu-builder::livewire.panel');
    }
}
