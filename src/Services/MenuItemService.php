<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Services;

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\Support\MenuHierarchy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MenuItemService
{
    protected FilamentMenuBuilderPlugin $plugin;

    public function __construct()
    {
        $this->plugin = FilamentMenuBuilderPlugin::get();
    }

    public function findById(int | string $id): ?Model
    {
        return $this->getModel()::query()->find($id);
    }

    public function findByIdWithRelations(int | string $id): ?Model
    {
        return $this->getModel()::query()
            ->where('id', $id)
            ->with('linkable')
            ->first();
    }

    public function hierarchy(Model $menu): MenuHierarchy
    {
        $items = $this->getModel()::query()
            ->where('menu_id', $menu->getKey())
            ->orderBy('order')
            ->toBase()
            ->get(['id', 'parent_id']);

        return new MenuHierarchy($items, $this->plugin->getMaxDepth($menu));
    }

    /**
     * Make the given items the ordered children of the given parent (or the root).
     *
     * @param  array<int|string>  $order
     */
    public function updateOrder(Model $menu, array $order, int | string | null $parentId = null): bool
    {
        if ($order === []) {
            return true;
        }

        return DB::transaction(function () use ($menu, $order, $parentId): bool {
            if (! $this->hierarchy($menu)->canMove($order, $parentId)) {
                return false;
            }

            $this->getModel()::query()
                ->where('menu_id', $menu->getKey())
                ->whereIn('id', $order)
                ->update([
                    'order' => DB::raw(
                        'case ' . collect($order)
                            ->map(
                                fn ($recordKey, int $recordIndex): string => 'when id = ' . DB::getPdo()->quote((string) $recordKey) . ' then ' . ($recordIndex + 1),
                            )
                            ->implode(' ') . ' end',
                    ),
                    'parent_id' => $parentId,
                ]);

            return true;
        });
    }

    public function indent(Model $menu, int | string $itemId): bool
    {
        return DB::transaction(function () use ($menu, $itemId): bool {
            $hierarchy = $this->hierarchy($menu);

            if (! $hierarchy->canIndent($itemId)) {
                return false;
            }

            $this->moveToEnd($menu, $itemId, $hierarchy->previousSiblingOf($itemId));

            return true;
        });
    }

    public function unindent(Model $menu, int | string $itemId): bool
    {
        return DB::transaction(function () use ($menu, $itemId): bool {
            $hierarchy = $this->hierarchy($menu);

            if (! $hierarchy->canUnindent($itemId)) {
                return false;
            }

            $grandparentId = $hierarchy->parentOf($hierarchy->parentOf($itemId));

            $this->moveToEnd($menu, $itemId, $grandparentId);

            return true;
        });
    }

    public function getMaxOrderForParent(int | string | null $parentId, int | string | null $menuId = null): int
    {
        $query = $this->getModel()::query()->where('parent_id', $parentId);

        if ($menuId) {
            $query->where('menu_id', $menuId);
        }

        return $query->max('order') ?? 0;
    }

    public function getSiblings(int | string $menuId, int | string | null $parentId): Collection
    {
        return $this->getModel()::query()
            ->where('menu_id', $menuId)
            ->where('parent_id', $parentId)
            ->orderBy('order')
            ->get();
    }

    public function reorderSiblings(int | string $menuId, int | string | null $parentId): void
    {
        $this->getSiblings($menuId, $parentId)->each(function (Model $sibling, int $index): void {
            $sibling->update(['order' => $index + 1]);
        });
    }

    public function delete(int | string $itemId): bool
    {
        $item = $this->findById($itemId);

        if (! $item) {
            return false;
        }

        return $item->delete();
    }

    public function update(int | string $itemId, array $data): bool
    {
        $model = $this->findById($itemId);

        if (! $model) {
            return false;
        }

        return $model->update($data);
    }

    /**
     * Move an item to the end of its new parent's children and close the gap it left behind.
     */
    protected function moveToEnd(Model $menu, int | string $itemId, int | string | null $parentId): void
    {
        $item = $this->findById($itemId);
        $previousParentId = $item->parent_id;

        $item->update([
            'parent_id' => $parentId,
            'order' => $this->getMaxOrderForParent($parentId, $menu->getKey()) + 1,
        ]);

        $this->reorderSiblings($menu->getKey(), $previousParentId);
    }

    protected function getModel(): string
    {
        return $this->plugin->getMenuItemModel();
    }
}
