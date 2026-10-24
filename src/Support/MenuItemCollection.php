<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Support;

use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Illuminate\Database\Eloquent\Collection;

/**
 * @template TKey of array-key
 * @template TModel of MenuItem
 *
 * @extends Collection<TKey, TModel>
 */
class MenuItemCollection extends Collection
{
    /**
     * @return static<int, TModel>
     */
    public function toTree(): static
    {
        // Walking down from the roots leaves out orphans and cycles.
        $childrenByParent = [];

        foreach ($this->sortBy('order') as $item) {
            $childrenByParent[(string) $item->parent_id][] = $item;
        }

        $attachChildren = function (MenuItem $item) use (&$attachChildren, $childrenByParent): void {
            $children = new static($childrenByParent[(string) $item->getKey()] ?? []);
            $children->each($attachChildren);

            $item->setRelation('children', $children);
        };

        $roots = new static($childrenByParent[''] ?? []);
        $roots->each($attachChildren);

        return $roots;
    }
}
