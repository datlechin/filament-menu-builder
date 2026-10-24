<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Services;

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\ItemTypes\ItemType;
use Datlechin\FilamentMenuBuilder\MenuBuilder;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Support\MenuHierarchy;
use Datlechin\FilamentMenuBuilder\Support\MenuItemCollection;
use Illuminate\Support\Facades\DB;

class MenuItemService
{
    public function __construct(protected FilamentMenuBuilderPlugin $plugin) {}

    public static function make(): static
    {
        return new static(FilamentMenuBuilderPlugin::get());
    }

    /**
     * @return MenuItemCollection<int, MenuItem>
     */
    public function items(Menu $menu): MenuItemCollection
    {
        return $menu->items()->with('linkable')->get();
    }

    /**
     * @param  MenuItemCollection<int, MenuItem>|null  $items
     */
    public function hierarchy(Menu $menu, ?MenuItemCollection $items = null): MenuHierarchy
    {
        $maxDepth = $this->plugin->getMaxDepth($menu);

        if (! $this->plugin->isNestingRestricted()) {
            return new MenuHierarchy($items ?? $menu->items()->toBase()->get(['id', 'parent_id']), $maxDepth);
        }

        $items = ($items ?? $menu->items()->get())->keyBy(fn (MenuItem $item): string => (string) $item->getKey());

        return new MenuHierarchy(
            $items,
            $maxDepth,
            fn (int | string $itemId, int | string | null $parentId): bool => $this->plugin->allowsNesting(
                $items[(string) $itemId],
                $parentId === null ? null : $items[(string) $parentId],
                $menu,
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<MenuItem>
     */
    public function create(Menu $menu, ItemType $type, array $data, int | string | null $parentId = null): array
    {
        return DB::transaction(function () use ($menu, $type, $data, $parentId): array {
            $items = $this->items($menu);
            $hierarchy = $this->hierarchy($menu, $items);
            $parent = $parentId === null ? null : $items->find($parentId);

            if ($parentId !== null && (! $parent || ! $hierarchy->contains($parentId))) {
                return [];
            }

            $maxDepth = $hierarchy->getMaxDepth();
            $depth = $parent ? $hierarchy->depthOf($parentId) + 1 : 0;
            $order = $items->where('parent_id', $parentId)->max('order') ?? 0;

            $newItems = array_map(
                fn (array $attributes): MenuItem => $menu->items()->make([
                    ...$attributes,
                    ...$type->getAttributes(),
                    'parent_id' => $parentId,
                    'order' => ++$order,
                ]),
                $type->getItemsToCreate($data),
            );

            // All items are rejected together, so a bulk add never lands half way.
            foreach ($newItems as $item) {
                if (($maxDepth !== null && $depth > $maxDepth) || ! $this->plugin->allowsNesting($item, $parent, $menu)) {
                    return [];
                }
            }

            foreach ($newItems as $item) {
                $item->save();
            }

            return $newItems;
        });
    }

    /**
     * @param  array<int|string>  $order
     */
    public function updateOrder(Menu $menu, array $order, int | string | null $parentId = null): bool
    {
        if ($order === []) {
            return true;
        }

        return DB::transaction(function () use ($menu, $order, $parentId): bool {
            if (! $this->hierarchy($menu)->canMove($order, $parentId)) {
                return false;
            }

            $this->writeOrder($menu, $order, $parentId);

            return true;
        });
    }

    public function indent(Menu $menu, int | string $itemId): bool
    {
        return DB::transaction(function () use ($menu, $itemId): bool {
            $hierarchy = $this->hierarchy($menu);

            if (! $hierarchy->canIndent($itemId)) {
                return false;
            }

            $this->moveToEnd($menu, $hierarchy, $itemId, $hierarchy->previousSiblingOf($itemId));

            return true;
        });
    }

    public function unindent(Menu $menu, int | string $itemId): bool
    {
        return DB::transaction(function () use ($menu, $itemId): bool {
            $hierarchy = $this->hierarchy($menu);

            if (! $hierarchy->canUnindent($itemId)) {
                return false;
            }

            $this->moveToEnd($menu, $hierarchy, $itemId, $hierarchy->parentOf($hierarchy->parentOf($itemId)));

            return true;
        });
    }

    public function moveUp(Menu $menu, int | string $itemId): bool
    {
        return $this->swapWithSibling($menu, $itemId, -1);
    }

    public function moveDown(Menu $menu, int | string $itemId): bool
    {
        return $this->swapWithSibling($menu, $itemId, 1);
    }

    public function duplicate(Menu $menu, int | string $itemId): ?MenuItem
    {
        return DB::transaction(function () use ($menu, $itemId): ?MenuItem {
            $items = $this->items($menu);
            // Loads every item's children in memory for the copy.
            $items->toTree();
            $hierarchy = $this->hierarchy($menu, $items);
            $item = $items->find($itemId);

            if (! $item || ! $hierarchy->contains($itemId)) {
                return null;
            }

            $parentId = $hierarchy->parentOf($itemId);
            $copy = $item->duplicateInto($menu, $parentId);

            $siblings = $hierarchy->childrenOf($parentId);
            array_splice($siblings, array_search($itemId, $siblings) + 1, 0, [$copy->getKey()]);

            $this->writeOrder($menu, $siblings, $parentId);

            return $copy;
        });
    }

    public function delete(Menu $menu, int | string $itemId): bool
    {
        return DB::transaction(function () use ($menu, $itemId): bool {
            $hierarchy = $this->hierarchy($menu);

            if (! $hierarchy->contains($itemId)) {
                return false;
            }

            $parentId = $hierarchy->parentOf($itemId);

            $menu->items()->whereKey([$itemId, ...$hierarchy->descendantsOf($itemId)])->delete();

            $this->writeOrder($menu, array_values(array_diff($hierarchy->childrenOf($parentId), [$itemId])), $parentId);

            return true;
        });
    }

    protected function swapWithSibling(Menu $menu, int | string $itemId, int $direction): bool
    {
        return DB::transaction(function () use ($menu, $itemId, $direction): bool {
            $hierarchy = $this->hierarchy($menu);
            $sibling = $direction < 0 ? $hierarchy->previousSiblingOf($itemId) : $hierarchy->nextSiblingOf($itemId);

            if ($sibling === null) {
                return false;
            }

            $parentId = $hierarchy->parentOf($itemId);
            $siblings = $hierarchy->childrenOf($parentId);
            $position = array_search($itemId, $siblings);

            [$siblings[$position], $siblings[$position + $direction]] = [$siblings[$position + $direction], $siblings[$position]];

            $this->writeOrder($menu, $siblings, $parentId);

            return true;
        });
    }

    protected function moveToEnd(Menu $menu, MenuHierarchy $hierarchy, int | string $itemId, int | string | null $parentId): void
    {
        $previousParentId = $hierarchy->parentOf($itemId);

        $this->writeOrder($menu, [...$hierarchy->childrenOf($parentId), $itemId], $parentId);
        $this->writeOrder($menu, array_values(array_diff($hierarchy->childrenOf($previousParentId), [$itemId])), $previousParentId);
    }

    /**
     * @param  array<int|string>  $order
     */
    protected function writeOrder(Menu $menu, array $order, int | string | null $parentId): void
    {
        if ($order !== []) {
            $menu->items()
                ->whereKey($order)
                ->update([
                    'parent_id' => $parentId,
                    'order' => DB::raw('case ' . collect($order)
                        ->map(fn (int | string $key, int $index): string => 'when id = ' . DB::getPdo()->quote((string) $key) . ' then ' . ($index + 1))
                        ->implode(' ') . ' end'),
                ]);
        }

        MenuBuilder::menuModel()::flushTreeCache($menu->getKey());
    }
}
