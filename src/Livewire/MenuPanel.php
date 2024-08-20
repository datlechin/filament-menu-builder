<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Livewire;

use Datlechin\FilamentMenuBuilder\Contracts\MenuPanel as ContractsMenuPanel;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Filament\Forms\Components;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

class MenuPanel extends Component implements HasForms
{
    use InteractsWithForms;

    public Menu $menu;

    public string $id;

    public string $name;

    public ?string $description;

    public ?string $icon;

    public bool $collapsible;

    public bool $collapsed;

    public bool $paginated;

    public int $perPage;

    public int $page = 1;

    public array $items = [];

    #[Validate('required|array')]
    public array $data = [];

    public function mount(ContractsMenuPanel $menuPanel): void
    {
        $this->id = $menuPanel->getIdentifier();
        $this->name = $menuPanel->getName();
        $this->description = $menuPanel->getDescription();
        $this->icon = $menuPanel->getIcon();
        $this->collapsible = $menuPanel->isCollapsible();
        $this->collapsed = $menuPanel->isCollapsed();
        $this->paginated = $menuPanel->isPaginated();
        $this->perPage = $menuPanel->getPerPage();
        $this->items = array_map(function ($item) {
            if (isset($item['url']) && is_callable($item['url'])) {
                $item['url'] = $item['url']();
            }

            return $item;
        }, $menuPanel->getItems());
    }

    public function getItems(): array
    {
        return $this->paginated
            ? collect($this->items)->forPage($this->page, $this->perPage)->all()
            : $this->items;
    }

    public function add(): void
    {
        $this->validate();

        $order = $this->menu->menuItems->max('order') ?? 0;

        $selectedItems = collect($this->items)
            ->filter(fn ($item) => in_array($item['title'], $this->data))
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

        Notification::make()
            ->title(__('filament-menu-builder::menu-builder.notifications.created.title'))
            ->success()
            ->send();
    }

    public function form(Form $form): Form
    {
        $items = collect($this->getItems())->mapWithKeys(fn ($item) => [$item['title'] => $item['title']]);

        return $form
            ->schema([
                Components\View::make('filament-tables::components.empty-state.index')
                    ->viewData([
                        'heading' => __('filament-menu-builder::menu-builder.panel.empty.heading'),
                        'description' => __('filament-menu-builder::menu-builder.panel.empty.description'),
                        'icon' => 'heroicon-o-link-slash',
                    ])
                    ->visible($items->isEmpty()),

                Components\CheckboxList::make('data')
                    ->hiddenLabel()
                    ->required()
                    ->bulkToggleable()
                    ->live(condition: $this->paginated)
                    ->visible($items->isNotEmpty())
                    ->options($items),
            ]);
    }

    public function getTotalPages(): int
    {
        return (int) ceil(count($this->items) / $this->perPage);
    }

    public function nextPage(): void
    {
        $this->page = min($this->getTotalPages(), $this->page + 1);
    }

    public function previousPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    public function hasNextPage(): bool
    {
        return $this->page < $this->getTotalPages();
    }

    public function hasPreviousPage(): bool
    {
        return $this->page > 1;
    }

    public function hasPages(): bool
    {
        return $this->paginated && $this->getTotalPages() > 1;
    }

    public function render(): View
    {
        return view('filament-menu-builder::livewire.panel');
    }
}
