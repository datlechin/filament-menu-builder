<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Concerns;

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\Services\MenuItemService;
use Datlechin\FilamentMenuBuilder\Support\MenuHierarchy;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Size;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Computed;

/**
 * @property-read MenuHierarchy $hierarchy
 */
trait ManagesMenuItemHierarchy
{
    protected ?MenuItemService $menuItemService = null;

    /**
     * A per-request snapshot of the menu tree, shared by every item's action
     * visibility check so rendering does not query once per item.
     */
    #[Computed]
    public function hierarchy(): MenuHierarchy
    {
        return $this->getMenuItemService()->hierarchy($this->menu);
    }

    /**
     * @param  array<int|string>  $order
     */
    public function reorder(array $order, int | string | null $parentId = null): void
    {
        if ($this->getMenuItemService()->updateOrder($this->menu, $order, $parentId)) {
            unset($this->hierarchy);

            return;
        }

        $this->notifyMoveRejected($order, $parentId);
    }

    public function indent(int | string $itemId): void
    {
        $this->getMenuItemService()->indent($this->menu, $itemId);

        unset($this->hierarchy);
    }

    public function unindent(int | string $itemId): void
    {
        $this->getMenuItemService()->unindent($this->menu, $itemId);

        unset($this->hierarchy);
    }

    public function canIndent(int | string $itemId): bool
    {
        return $this->hierarchy->canIndent($itemId);
    }

    public function canUnindent(int | string $itemId): bool
    {
        return $this->hierarchy->canUnindent($itemId);
    }

    public function indentAction(): Action
    {
        return Action::make('indent')
            ->label(__('filament-menu-builder::menu-builder.actions.indent'))
            ->icon('heroicon-m-arrow-right')
            ->color('gray')
            ->iconButton()
            ->size(Size::Small)
            ->record(fn (array $arguments): ?Model => $this->getMenuItemService()->findById($arguments['id']))
            ->action(fn (Model $record) => $this->indent($record->getKey()))
            ->visible(fn (?Model $record): bool => $record !== null && $this->isIndentActionVisible($record->getKey()));
    }

    public function unindentAction(): Action
    {
        return Action::make('unindent')
            ->label(__('filament-menu-builder::menu-builder.actions.unindent'))
            ->icon('heroicon-m-arrow-left')
            ->color('gray')
            ->iconButton()
            ->size(Size::Small)
            ->record(fn (array $arguments): ?Model => $this->getMenuItemService()->findById($arguments['id']))
            ->action(fn (Model $record) => $this->unindent($record->getKey()))
            ->visible(fn (?Model $record): bool => $record !== null && $this->isUnindentActionVisible($record->getKey()));
    }

    protected function isIndentActionVisible(int | string $itemId): bool
    {
        return FilamentMenuBuilderPlugin::get()->isIndentActionsEnabled() &&
               $this->canIndent($itemId);
    }

    protected function isUnindentActionVisible(int | string $itemId): bool
    {
        return FilamentMenuBuilderPlugin::get()->isIndentActionsEnabled() &&
               $this->canUnindent($itemId);
    }

    /**
     * @param  array<int|string>  $order
     */
    protected function notifyMoveRejected(array $order, int | string | null $parentId): void
    {
        $maxDepth = $this->hierarchy->getMaxDepth();

        Notification::make()
            ->title(__('filament-menu-builder::menu-builder.notifications.move_rejected.title'))
            ->body($this->hierarchy->exceedsMaxDepth($order, $parentId)
                ? trans_choice('filament-menu-builder::menu-builder.notifications.move_rejected.body', $maxDepth, ['depth' => $maxDepth])
                : null)
            ->danger()
            ->send();
    }

    protected function getMenuItemService(): MenuItemService
    {
        if ($this->menuItemService === null) {
            $this->menuItemService = app(MenuItemService::class);
        }

        return $this->menuItemService;
    }
}
