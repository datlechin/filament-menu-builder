@props([
    'items',
    'hierarchy',
    'canEdit',
    'parentId' => null,
])

<ul
    x-data="menuBuilder({ parentId: @js($parentId), maxDepth: @js($hierarchy->getMaxDepth()) })"
    data-sortable-depth="{{ $parentId === null ? 0 : $hierarchy->depthOf($parentId) + 1 }}"
    {{ $attributes->grid()->class(['fi-fo-repeater-items']) }}
>
    @foreach ($items as $item)
        <x-filament-menu-builder::tree.item :item="$item" :hierarchy="$hierarchy" :can-edit="$canEdit" />
    @endforeach
</ul>
