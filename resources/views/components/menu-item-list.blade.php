@props([
    'items',
    'hierarchy',
    'parentId' => null,
    'startActions' => [],
    'endActions' => [],
])

@php
    /**
     * @var \Illuminate\Support\Collection<int, \Datlechin\FilamentMenuBuilder\Models\MenuItem> $items
     * @var \Datlechin\FilamentMenuBuilder\Support\MenuHierarchy $hierarchy
     * @var array<\Filament\Actions\Action> $startActions
     * @var array<\Filament\Actions\Action> $endActions
     */

    $depth = $parentId === null ? 0 : $hierarchy->depthOf($parentId) + 1;
@endphp

{{-- Filament's grid sizes its track as minmax(0, 1fr), so items always fit the list instead of growing to their content. --}}
<ul
    x-data="menuBuilder({ parentId: @js($parentId), maxDepth: @js($hierarchy->getMaxDepth()) })"
    data-sortable-depth="{{ $depth }}"
    {{ $attributes->grid()->class(['fi-fo-repeater-items']) }}
>
    @foreach ($items as $item)
        <x-filament-menu-builder::menu-item
            :item="$item"
            :hierarchy="$hierarchy"
            :start-actions="$startActions"
            :end-actions="$endActions"
        />
    @endforeach
</ul>
