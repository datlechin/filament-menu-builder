<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Livewire;

use Closure;
use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\ItemTypes\ItemType;
use Datlechin\FilamentMenuBuilder\ItemTypes\UnregisteredItemType;
use Datlechin\FilamentMenuBuilder\MenuBuilder;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Services\MenuItemService;
use Datlechin\FilamentMenuBuilder\Support\MenuHierarchy;
use Datlechin\FilamentMenuBuilder\Support\MenuItemCollection;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read MenuItemCollection<string, MenuItem> $items
 * @property-read MenuItemCollection<int, MenuItem> $tree
 * @property-read MenuHierarchy $hierarchy
 */
class MenuItems extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public Menu $menu;

    #[Computed]
    public function items(): MenuItemCollection
    {
        return $this->getService()->items($this->menu)->keyBy(fn (MenuItem $item): string => (string) $item->getKey());
    }

    #[Computed]
    public function tree(): MenuItemCollection
    {
        return $this->items->toTree();
    }

    #[Computed]
    public function hierarchy(): MenuHierarchy
    {
        return $this->getService()->hierarchy($this->menu, $this->items);
    }

    /**
     * @param  array<int|string>  $order
     */
    public function reorder(array $order, int | string | null $parentId = null): void
    {
        abort_unless($this->canEdit(), 403);

        if ($this->getService()->updateOrder($this->menu, $order, $parentId)) {
            $this->refreshTree();

            return;
        }

        $this->notifyRejected(fn (MenuHierarchy $hierarchy): bool => $hierarchy->exceedsMaxDepth($order, $parentId));
    }

    public function createItemAction(): CreateAction
    {
        return CreateAction::make('createItem')
            ->model(MenuBuilder::menuItemModel())
            ->label(fn (array $arguments): string => filled($arguments['parent'] ?? null)
                ? __('filament-menu-builder::menu-builder.actions.add_child', ['type' => $this->getItemType($arguments)->getLabel()])
                : $this->getItemType($arguments)->getLabel())
            ->icon(fn (array $arguments): ?string => $this->getItemType($arguments)->getIcon())
            ->modalHeading(fn (array $arguments): string => __('filament-menu-builder::menu-builder.actions.create.heading', ['type' => $this->getItemType($arguments)->getLabel()]))
            ->slideOver()
            ->modalWidth(Width::Large)
            ->schema(fn (array $arguments): array => $this->getItemType($arguments)->getCreateSchema())
            ->using(function (array $data, array $arguments, CreateAction $action): MenuItem {
                $parentId = $arguments['parent'] ?? null;
                $items = $this->getService()->create($this->menu, $this->getItemType($arguments), $data, $parentId);

                if ($items === []) {
                    $this->notifyRejected(fn (MenuHierarchy $hierarchy): bool => $parentId !== null
                        && $hierarchy->getMaxDepth() !== null
                        && $hierarchy->depthOf($parentId) + 1 > $hierarchy->getMaxDepth());

                    $action->halt();
                }

                $this->refreshTree();

                return end($items);
            })
            ->visible(fn (array $arguments): bool => $this->getItemType($arguments)->isVisible($this->menu)
                && (blank($arguments['parent'] ?? null) || $this->canAddChildTo($arguments['parent'])))
            ->authorize(fn (): bool => $this->canEdit());
    }

    public function editItemAction(): EditAction
    {
        return EditAction::make('editItem')
            ->record(fn (array $arguments): ?MenuItem => $this->findItem($arguments['item'] ?? null))
            ->iconButton()
            ->icon('heroicon-m-pencil-square')
            ->color('gray')
            ->size(Size::Small)
            ->modalHeading(fn (?MenuItem $record): ?string => $record?->translated('title'))
            ->slideOver()
            ->modalWidth(Width::Large)
            ->schema(fn (?MenuItem $record): array => $record ? $this->getItemTypeFor($record)->getFormSchema() : [])
            ->after(fn () => $this->refreshTree())
            ->authorize(fn (?MenuItem $record): bool => $record !== null && $this->canEdit());
    }

    public function deleteItemAction(): DeleteAction
    {
        return DeleteAction::make('deleteItem')
            ->record(fn (array $arguments): ?MenuItem => $this->findItem($arguments['item'] ?? null))
            ->hidden(fn (?MenuItem $record): bool => $record === null)
            ->modalDescription(fn (?MenuItem $record): ?string => $record && ($count = count($this->hierarchy->descendantsOf($record->getKey())))
                ? trans_choice('filament-menu-builder::menu-builder.actions.delete.description', $count, ['count' => $count])
                : null)
            ->using(fn (MenuItem $record): bool => $this->getService()->delete($this->menu, $record->getKey()))
            ->after(fn () => $this->refreshTree())
            ->authorize(fn (?MenuItem $record): bool => $record !== null && $this->canEdit());
    }

    public function duplicateItemAction(): Action
    {
        return $this->makeItemAction('duplicateItem', 'heroicon-m-square-2-stack')
            ->action(function (array $arguments): void {
                $this->getService()->duplicate($this->menu, $arguments['item']);
                $this->refreshTree();

                Notification::make()
                    ->title(__('filament-menu-builder::menu-builder.notifications.duplicated.title'))
                    ->success()
                    ->send();
            });
    }

    public function moveUpAction(): Action
    {
        return $this->makeMoveAction(
            'moveUp',
            'heroicon-m-arrow-up',
            fn (int | string $itemId): bool => $this->hierarchy->previousSiblingOf($itemId) !== null,
            fn (int | string $itemId): bool => $this->getService()->moveUp($this->menu, $itemId),
        );
    }

    public function moveDownAction(): Action
    {
        return $this->makeMoveAction(
            'moveDown',
            'heroicon-m-arrow-down',
            fn (int | string $itemId): bool => $this->hierarchy->nextSiblingOf($itemId) !== null,
            fn (int | string $itemId): bool => $this->getService()->moveDown($this->menu, $itemId),
        );
    }

    public function indentAction(): Action
    {
        return $this->makeMoveAction(
            'indent',
            'heroicon-m-arrow-right',
            fn (int | string $itemId): bool => $this->hierarchy->canIndent($itemId),
            fn (int | string $itemId): bool => $this->getService()->indent($this->menu, $itemId),
        );
    }

    public function unindentAction(): Action
    {
        return $this->makeMoveAction(
            'unindent',
            'heroicon-m-arrow-left',
            fn (int | string $itemId): bool => $this->hierarchy->canUnindent($itemId),
            fn (int | string $itemId): bool => $this->getService()->unindent($this->menu, $itemId),
        );
    }

    public function getAddItemActions(): ActionGroup | Action
    {
        $actions = $this->getCreateActions();

        if (count($actions) === 1) {
            return $actions[0]->button();
        }

        return ActionGroup::make($actions)
            ->label(__('filament-menu-builder::menu-builder.actions.add_item'))
            ->icon('heroicon-m-plus')
            ->button();
    }

    public function getItemActions(int | string $itemId): ActionGroup
    {
        $arguments = ['item' => $itemId];

        return ActionGroup::make([
            ActionGroup::make($this->getCreateActions($itemId))->dropdown(false),
            ActionGroup::make([
                ($this->duplicateItemAction)($arguments),
                ($this->moveUpAction)($arguments),
                ($this->moveDownAction)($arguments),
                ($this->indentAction)($arguments),
                ($this->unindentAction)($arguments),
            ])->dropdown(false),
            ActionGroup::make([
                ($this->deleteItemAction)($arguments),
            ])->dropdown(false),
        ])
            ->size(Size::Small)
            ->color('gray');
    }

    public function getItemType(array $arguments): ItemType
    {
        return FilamentMenuBuilderPlugin::get()->getItemType($arguments['type'] ?? '') ?? abort(404);
    }

    public function getItemTypeFor(MenuItem $item): ItemType
    {
        return FilamentMenuBuilderPlugin::get()->getItemTypeFor($item) ?? UnregisteredItemType::make($item->type);
    }

    public function canEdit(): bool
    {
        return FilamentMenuBuilderPlugin::get()->getResource()::canEdit($this->menu);
    }

    public function render(): View
    {
        return view('filament-menu-builder::livewire.menu-items');
    }

    /**
     * @return list<Action>
     */
    protected function getCreateActions(int | string | null $parentId = null): array
    {
        return collect(FilamentMenuBuilderPlugin::get()->getItemTypes())
            ->filter(fn (ItemType $type): bool => $type->isVisible($this->menu))
            ->map(fn (ItemType $type, string $key): Action => ($this->createItemAction)(['type' => $key, 'parent' => $parentId]))
            ->values()
            ->all();
    }

    protected function makeItemAction(string $name, string $icon): Action
    {
        return Action::make($name)
            ->label(__("filament-menu-builder::menu-builder.actions.{$name}"))
            ->icon($icon)
            ->authorize(fn (array $arguments): bool => $this->canEdit() && $this->findItem($arguments['item'] ?? null) !== null);
    }

    /**
     * @param  Closure(int|string): bool  $isPossible
     * @param  Closure(int|string): bool  $move
     */
    protected function makeMoveAction(string $name, string $icon, Closure $isPossible, Closure $move): Action
    {
        return $this->makeItemAction($name, $icon)
            ->visible(fn (array $arguments): bool => $isPossible($arguments['item']))
            ->action(function (array $arguments) use ($move): void {
                $move($arguments['item']);
                $this->refreshTree();
            });
    }

    protected function findItem(int | string | null $itemId): ?MenuItem
    {
        return $this->items[(string) $itemId] ?? null;
    }

    protected function canAddChildTo(int | string $parentId): bool
    {
        $maxDepth = $this->hierarchy->getMaxDepth();

        return $this->hierarchy->contains($parentId)
            && ($maxDepth === null || $this->hierarchy->depthOf($parentId) < $maxDepth);
    }

    protected function refreshTree(): void
    {
        unset($this->items, $this->tree, $this->hierarchy);
    }

    /**
     * @param  callable(MenuHierarchy): bool  $exceedsMaxDepth
     */
    protected function notifyRejected(callable $exceedsMaxDepth): void
    {
        $maxDepth = $this->hierarchy->getMaxDepth();

        Notification::make()
            ->title(__('filament-menu-builder::menu-builder.notifications.move_rejected.title'))
            ->body($exceedsMaxDepth($this->hierarchy)
                ? trans_choice('filament-menu-builder::menu-builder.notifications.move_rejected.body', $maxDepth, ['depth' => $maxDepth])
                : __('filament-menu-builder::menu-builder.notifications.move_rejected.nesting'))
            ->danger()
            ->send();
    }

    protected function getService(): MenuItemService
    {
        return MenuItemService::make();
    }
}
