<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Support;

use ArrayIterator;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * @implements Arrayable<int, array<string, mixed>>
 * @implements IteratorAggregate<int, MenuNode>
 */
final class MenuTree implements Arrayable, Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @param  list<MenuNode>  $nodes
     */
    public function __construct(private readonly array $nodes = []) {}

    public static function fromItems(MenuItemCollection $items, ?string $locale = null): self
    {
        return new self(self::buildNodes($items->where('is_visible', true)->toTree(), $locale, 0));
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     */
    public static function fromCacheArray(array $nodes): self
    {
        return new self(array_map(MenuNode::fromCacheArray(...), $nodes));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toCacheArray(): array
    {
        return array_map(fn (MenuNode $node): array => $node->toCacheArray(), $this->nodes);
    }

    /**
     * @return list<MenuNode>
     */
    public function nodes(): array
    {
        return $this->nodes;
    }

    public function isEmpty(): bool
    {
        return $this->nodes === [];
    }

    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    public function count(): int
    {
        return count($this->nodes);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->nodes);
    }

    /**
     * @param  callable(MenuNode): bool  $callback
     */
    public function filter(callable $callback): self
    {
        return new self(self::filterNodes($this->nodes, $callback));
    }

    /**
     * @return list<MenuNode>
     */
    public function activeTrail(?string $currentUrl = null): array
    {
        $currentUrl ??= request()->fullUrl();
        $trail = [];
        $nodes = $this->nodes;

        while ($node = collect($nodes)->first(fn (MenuNode $node): bool => $node->isActiveTrail($currentUrl))) {
            $trail[] = $node;
            $nodes = $node->children;
        }

        return $trail;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(fn (MenuNode $node): array => $node->toArray(), $this->nodes);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return list<MenuNode>
     */
    private static function buildNodes(MenuItemCollection $items, ?string $locale, int $depth): array
    {
        $nodes = [];

        foreach ($items as $item) {
            $url = $item->getUrl($locale);

            // A link whose route or record is gone is hidden together with its children.
            if ($url === null && $item->hasDestination()) {
                continue;
            }

            $nodes[] = MenuNode::fromItem($item, $url, $locale, $depth, self::buildNodes($item->children, $locale, $depth + 1));
        }

        return $nodes;
    }

    /**
     * @param  list<MenuNode>  $nodes
     * @param  callable(MenuNode): bool  $callback
     * @return list<MenuNode>
     */
    private static function filterNodes(array $nodes, callable $callback): array
    {
        $filtered = [];

        foreach ($nodes as $node) {
            if ($callback($node)) {
                $filtered[] = $node->withChildren(self::filterNodes($node->children, $callback));
            }
        }

        return $filtered;
    }
}
