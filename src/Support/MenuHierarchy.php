<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Support;

/**
 * An immutable, in-memory snapshot of a single menu's item tree.
 *
 * Depth is zero-based: root items have a depth of 0. The height of an item is
 * the depth of its deepest descendant relative to the item itself, so a leaf
 * has a height of 0. Moving an item moves its whole subtree, so a move is only
 * allowed when `depth(new position) + height(item) <= maxDepth`.
 *
 * Only items reachable from the root are part of the hierarchy, which keeps
 * orphaned or cyclic rows from ever being treated as valid move targets.
 */
final class MenuHierarchy
{
    private const ROOT = '';

    /** @var array<string, int|string> */
    private array $keys = [];

    /** @var array<string, string> */
    private array $parents = [];

    /** @var array<string, list<string>> */
    private array $children = [];

    /** @var array<string, int> */
    private array $depths = [];

    /** @var array<string, int> */
    private array $heights = [];

    /**
     * @param  iterable<object{id: int|string, parent_id: int|string|null}>  $items  Items of one menu, ordered by `order`.
     */
    public function __construct(iterable $items, private readonly ?int $maxDepth = null)
    {
        $children = [];

        foreach ($items as $item) {
            $key = (string) $item->id;

            $this->keys[$key] = $item->id;
            $children[self::normalize($item->parent_id)][] = $key;
        }

        $this->build($children);
    }

    public function getMaxDepth(): ?int
    {
        return $this->maxDepth;
    }

    public function contains(int | string $itemId): bool
    {
        return isset($this->depths[(string) $itemId]);
    }

    public function parentOf(int | string $itemId): int | string | null
    {
        return $this->keyOf($this->parents[(string) $itemId] ?? self::ROOT);
    }

    public function depthOf(int | string $itemId): int
    {
        return $this->depths[(string) $itemId] ?? 0;
    }

    public function heightOf(int | string $itemId): int
    {
        return $this->heights[(string) $itemId] ?? 0;
    }

    public function previousSiblingOf(int | string $itemId): int | string | null
    {
        $key = (string) $itemId;

        if (! $this->contains($key)) {
            return null;
        }

        $siblings = $this->children[$this->parents[$key]];
        $position = array_search($key, $siblings, true);

        return $position > 0 ? $this->keyOf($siblings[$position - 1]) : null;
    }

    public function isDescendantOf(int | string $itemId, int | string $ancestorId): bool
    {
        $ancestor = (string) $ancestorId;
        $current = $this->parents[(string) $itemId] ?? self::ROOT;

        while ($current !== self::ROOT) {
            if ($current === $ancestor) {
                return true;
            }

            $current = $this->parents[$current];
        }

        return false;
    }

    public function canIndent(int | string $itemId): bool
    {
        $previousSibling = $this->previousSiblingOf($itemId);

        return $previousSibling !== null
            && $this->fitsWithinMaxDepth($this->depthOf($previousSibling) + 1 + $this->heightOf($itemId));
    }

    public function canUnindent(int | string $itemId): bool
    {
        return $this->contains($itemId) && $this->parentOf($itemId) !== null;
    }

    /**
     * Determine whether the given items may become the (ordered) children of the given parent.
     *
     * @param  array<int|string>  $itemIds
     */
    public function canMove(array $itemIds, int | string | null $parentId): bool
    {
        $parent = self::normalize($parentId);

        if ($parent !== self::ROOT && ! $this->contains($parent)) {
            return false;
        }

        foreach ($itemIds as $itemId) {
            $key = (string) $itemId;

            if (! $this->contains($key)) {
                return false;
            }

            if ($parent === $key || ($parent !== self::ROOT && $this->isDescendantOf($parent, $key))) {
                return false;
            }
        }

        return ! $this->exceedsMaxDepth($itemIds, $parentId);
    }

    /**
     * Determine whether moving the given items under the given parent would nest any of them too deep.
     *
     * Items that already belong to the parent are only being reordered, so the depth
     * limit is not applied to them. This keeps menus that were built before a limit
     * was configured editable, while never letting them grow any deeper.
     *
     * @param  array<int|string>  $itemIds
     */
    public function exceedsMaxDepth(array $itemIds, int | string | null $parentId): bool
    {
        $parent = self::normalize($parentId);

        if ($parent !== self::ROOT && ! $this->contains($parent)) {
            return false;
        }

        $depth = $parent === self::ROOT ? 0 : $this->depths[$parent] + 1;

        foreach ($itemIds as $itemId) {
            $key = (string) $itemId;

            if ($this->contains($key) && $this->parents[$key] !== $parent && ! $this->fitsWithinMaxDepth($depth + $this->heights[$key])) {
                return true;
            }
        }

        return false;
    }

    private function fitsWithinMaxDepth(int $depth): bool
    {
        return $this->maxDepth === null || $depth <= $this->maxDepth;
    }

    /**
     * Walk the tree breadth-first from the root, recording parents and depths, then
     * derive heights bottom-up by visiting the same order in reverse. Rows that are
     * not reachable from the root (orphans, cycles) are never visited.
     *
     * @param  array<string, list<string>>  $children
     */
    private function build(array $children): void
    {
        $order = [self::ROOT];

        for ($index = 0; $index < count($order); $index++) {
            $parent = $order[$index];
            $this->children[$parent] = $children[$parent] ?? [];

            foreach ($this->children[$parent] as $key) {
                $this->parents[$key] = $parent;
                $this->depths[$key] = $parent === self::ROOT ? 0 : $this->depths[$parent] + 1;
                $this->heights[$key] = 0;

                $order[] = $key;
            }
        }

        foreach (array_reverse(array_slice($order, 1)) as $key) {
            $parent = $this->parents[$key];

            if ($parent !== self::ROOT) {
                $this->heights[$parent] = max($this->heights[$parent], $this->heights[$key] + 1);
            }
        }
    }

    private function keyOf(string $key): int | string | null
    {
        return $key === self::ROOT ? null : $this->keys[$key];
    }

    private static function normalize(int | string | null $itemId): string
    {
        return $itemId === null ? self::ROOT : (string) $itemId;
    }
}
