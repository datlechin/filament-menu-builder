@props([
    'item',
])

@php
    /** @var \Datlechin\FilamentMenuBuilder\Models\MenuItem $item */

    $hasChildren = $item->children->isNotEmpty();
@endphp

<li
    wire:key="{{ $item->getKey() }}"
    data-sortable-item="{{ $item->getKey() }}"
    x-data="{ open: $persist(true).as('menu-item-' + {{ $item->getKey() }}) }"
>
    <div
        class="flex justify-between px-3 py-2 bg-white shadow-sm rounded-xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
    >
        <div class="flex flex-1 items-center gap-2 truncate">
            {{ $this->reorderAction }}

            @if ($hasChildren)
                <x-filament::icon-button
                    icon="heroicon-o-chevron-right"
                    x-on:click="open = !open"
                    x-bind:title="open ? '{{ trans('filament-menu-builder::menu-builder.items.collapse') }}' : '{{ trans('filament-menu-builder::menu-builder.items.expand') }}'"
                    color="gray"
                    class="transition duration-200 ease-in-out"
                    x-bind:class="{ 'rotate-90': open }"
                    size="sm"
                />
            @endif

            @if (\Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin::get()->isIndentActionsEnabled())
                {{ ($this->unindentAction)(['id' => $item->getKey()]) }}
                {{ ($this->indentAction)(['id' => $item->getKey()]) }}
            @endif

            <div class="text-sm font-medium leading-6 text-gray-950 dark:text-white whitespace-nowrap">
                {{ $item->title }}
            </div>

            <div class="hidden overflow-hidden text-sm text-gray-500 sm:block dark:text-gray-400 whitespace-nowrap text-ellipsis">
                {{ $item->url }}
            </div>
        </div>
        <div class="flex items-center gap-2">
            <x-filament::badge :color="$item->type === 'internal' ? 'primary' : 'gray'" class="hidden sm:block">
                {{ $item->type }}
            </x-filament::badge>
            {{ ($this->editAction)(['id' => $item->getKey(), 'title' => $item->title]) }}
            {{ ($this->deleteAction)(['id' => $item->getKey(), 'title' => $item->title]) }}
        </div>
    </div>

    <ul
        x-collapse
        x-show="open"
        wire:key="{{ $item->getKey() }}.children"
        x-data="menuBuilder({ parentId: {{ $item->getKey()  }} })"
        class="mt-2 space-y-2 ms-4"
    >
        @foreach ($item->children as $child)
            <x-filament-menu-builder::menu-item :item="$child" />
        @endforeach
    </ul>
</li>
