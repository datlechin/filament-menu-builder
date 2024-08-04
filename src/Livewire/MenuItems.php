<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Livewire;

use Datlechin\FilamentMenuBuilder\Enums\LinkTarget;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class MenuItems extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public Menu $menu;

    #[Computed]
    #[On('menu:created')]
    public function menuItems(): Collection
    {
        return $this->menu->menuItems()->get();
    }

    public function reorder(array $order, ?string $parentId = null): void
    {
        if (empty($order)) {
            return;
        }

        MenuItem::query()
            ->whereIn('id', $order)
            ->update([
                'order' => DB::raw(
                    'case ' . collect($order)
                        ->map(fn($recordKey, int $recordIndex): string => 'when id = ' . DB::getPdo()->quote($recordKey) . ' then ' . ($recordIndex + 1))
                        ->implode(' ') . ' end',
                ),
                'parent_id' => $parentId,
            ]);
    }

    public function reorderAction(): Action
    {
        return Action::make('reorder')
            ->label(__('filament-forms::components.builder.actions.reorder.label'))
            ->icon(FilamentIcon::resolve('forms::components.builder.actions.reorder') ?? 'heroicon-m-arrows-up-down')
            ->color('gray')
            ->iconButton()
            ->extraAttributes(['data-sortable-handle' => true, 'class' => 'cursor-move'])
            ->livewireClickHandlerEnabled(false)
            ->size(ActionSize::Small);
    }

    public function editAction(): Action
    {
        return Action::make('edit')
            ->label(__('filament-actions::edit.single.label'))
            ->iconButton()
            ->size(ActionSize::Small)
            ->modalHeading(fn(array $arguments): string => __('filament-actions::edit.single.modal.heading', ['label' => $arguments['title']]))
            ->icon('heroicon-m-pencil-square')
            ->fillForm(fn(array $arguments): array => MenuItem::query()
                ->where('id', $arguments['id'])
                ->with('linkable')
                ->first()
                ->toArray())
            ->form([
                TextInput::make('title')
                    ->label(__('filament-menu-builder::menu-builder.title'))
                    ->required(),
                TextInput::make('url')
                    ->hidden(fn(?string $state): bool => empty($state))
                    ->label(__('filament-menu-builder::menu-builder.url'))
                    ->required(),
                Placeholder::make('linkable_type')
                    ->hidden(fn(?string $state): bool => empty($state))
                    ->content(fn(string $state) => $state),
                Placeholder::make('linkable_id')
                    ->hidden(fn(?string $state): bool => empty($state))
                    ->content(fn(string $state) => $state),
                Select::make('target')
                    ->label(__('filament-menu-builder::menu-builder.open_in.label'))
                    ->options(LinkTarget::class)
                    ->default(LinkTarget::Self),
            ])
            ->action(
                fn(array $data, array $arguments) => MenuItem::query()
                    ->where('id', $arguments['id'])
                    ->update($data),
            )
            ->modalWidth(MaxWidth::Medium)
            ->slideOver();
    }

    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->label(__('filament-actions::delete.single.label'))
            ->color('danger')
            ->groupedIcon(FilamentIcon::resolve('actions::delete-action.grouped') ?? 'heroicon-m-trash')
            ->icon('heroicon-s-trash')
            ->iconButton()
            ->size(ActionSize::Small)
            ->requiresConfirmation()
            ->modalHeading(fn(array $arguments): string => __('filament-actions::delete.single.modal.heading', ['label' => $arguments['title']]))
            ->modalSubmitActionLabel(__('filament-actions::delete.single.modal.actions.delete.label'))
            ->modalIcon(FilamentIcon::resolve('actions::delete-action.modal') ?? 'heroicon-o-trash')
            ->action(function (array $arguments): void {
                $menuItem = MenuItem::query()->where('id', $arguments['id'])->first();

                $menuItem?->delete();
            });
    }

    public function render(): View
    {
        return view('filament-menu-builder::livewire.menu-items');
    }
}
