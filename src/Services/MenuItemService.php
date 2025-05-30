<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Services;

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MenuItemService
{
    public function __construct(
        protected FilamentMenuBuilderPlugin $plugin = new FilamentMenuBuilderPlugin(),
    ) {
        $this->plugin = FilamentMenuBuilderPlugin::get();
    }

    public function findById(int $id): ?Model
    {
        return $this->getModel()::query()->find($id);
    }

    public function findByIdWithRelations(int $id): ?Model
    {
        return $this->getModel()::query()
            ->where('id', $id)
            ->with('linkable')
            ->first();
    }

    public function updateOrder(array $order, ?string $parentId = null): void
    {
        if (empty($order)) {
            return;
        }

        $this->getModel()::query()
            ->whereIn('id', $order)
            ->update([
                'order' => DB::raw(
                    'case ' . collect($order)
                        ->map(
                            fn($recordKey, int $recordIndex): string =>
                            'when id = ' . DB::getPdo()->quote($recordKey) . ' then ' . ($recordIndex + 1),
                        )
                        ->implode(' ') . ' end',
                ),
                'parent_id' => $parentId,
            ]);
    }

    public function getPreviousSibling(Model $item): ?Model
    {
        return $this->getModel()::query()
            ->where('menu_id', $item->menu_id)
            ->where('parent_id', $item->parent_id)
            ->where('order', '<', $item->order)
            ->orderByDesc('order')
            ->first();
    }

    public function getMaxOrderForParent(?int $parentId, ?int $menuId = null): int
    {
        $query = $this->getModel()::query()->where('parent_id', $parentId);

        if ($menuId) {
            $query->where('menu_id', $menuId);
        }

        return $query->max('order') ?? 0;
    }

    public function getSiblings(?int $parentId): Collection
    {
        return $this->getModel()::query()
            ->where('parent_id', $parentId)
            ->orderBy('order')
            ->get();
    }

    public function reorderSiblings(?int $parentId): void
    {
        $siblings = $this->getSiblings($parentId);

        $siblings->each(function ($sibling, $index) {
            $sibling->update(['order' => $index + 1]);
        });
    }

    public function indent(int $itemId): bool
    {
        $item = $this->findById($itemId);

        if (!$item) {
            return false;
        }

        $previousSibling = $this->getPreviousSibling($item);

        if (!$previousSibling) {
            return false;
        }

        $maxOrder = $this->getMaxOrderForParent($previousSibling->id);
        $originalParentId = $item->getOriginal('parent_id');

        $item->update([
            'parent_id' => $previousSibling->id,
            'order' => $maxOrder + 1,
        ]);

        $this->reorderSiblings($originalParentId);

        return true;
    }

    public function unindent(int $itemId): bool
    {
        $item = $this->findById($itemId);

        if (!$item || !$item->parent_id) {
            return false;
        }

        $parent = $item->parent;
        if (!$parent) {
            return false;
        }

        $maxOrder = $this->getMaxOrderForParent($parent->parent_id, $item->menu_id);
        $oldParentId = $item->parent_id;

        $item->update([
            'parent_id' => $parent->parent_id,
            'order' => $maxOrder + 1,
        ]);

        $this->reorderSiblings($oldParentId);

        return true;
    }

    public function canIndent(int $itemId): bool
    {
        $item = $this->findById($itemId);

        if (!$item) {
            return false;
        }

        return $this->getPreviousSibling($item) !== null;
    }

    public function canUnindent(int $itemId): bool
    {
        $item = $this->findById($itemId);

        return $item && $item->parent_id !== null;
    }

    public function delete(int $itemId): bool
    {
        $item = $this->findById($itemId);

        if (!$item) {
            return false;
        }

        return $item->delete();
    }

    public function update(int $itemId, array $data): bool
    {
        return $this->getModel()::query()
            ->where('id', $itemId)
            ->update($data) > 0;
    }

    protected function getModel(): string
    {
        return $this->plugin->getMenuItemModel();
    }
}
