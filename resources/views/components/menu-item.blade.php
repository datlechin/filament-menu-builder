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
    x-data="{ open: $persist(true).as('menu-item-' + @js($item->getKey())) }"
    class="fi-fo-repeater-item"
>
    <div class="fi-fo-repeater-item-header">
        <div class="fi-fo-repeater-item-header-start-actions">
            <x-filament::icon-button
                icon="heroicon-m-arrows-up-down"
                color="gray"
                size="sm"
                data-sortable-handle
                class="fi-menu-builder-item-handle"
            />

            @if ($hasChildren)
                <x-filament::icon-button
                    icon="heroicon-o-chevron-right"
                    x-on:click="open = !open"
                    x-bind:title="open ? '{{ trans('filament-menu-builder::menu-builder.items.collapse') }}' : '{{ trans('filament-menu-builder::menu-builder.items.expand') }}'"
                    color="gray"
                    x-bind:class="{ 'rotate-90': open }"
                    class="transition duration-200"
                    size="sm"
                />
            @endif

            @if (\Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin::get()->isIndentActionsEnabled())
                <x-filament::icon-button
                    icon="heroicon-m-arrow-left"
                    color="gray"
                    size="sm"
                    wire:click="unindent({{ Js::from($item->getKey()) }})"
                />
                <x-filament::icon-button
                    icon="heroicon-m-arrow-right"
                    color="gray"
                    size="sm"
                    wire:click="indent({{ Js::from($item->getKey()) }})"
                />
            @endif
        </div>

        <span class="fi-fo-repeater-item-header-label fi-truncated fi-menu-builder-item-label">
            @if ($item->icon)
                <x-filament::icon :icon="$item->icon" class="fi-menu-builder-item-label-icon" />
            @endif
            {{ $item->resolveLocale($item->title) }}
        </span>

        <span class="fi-fo-repeater-item-header-label fi-truncated fi-menu-builder-item-url">
            {{ $item->url }}
        </span>

        <div class="fi-fo-repeater-item-header-end-actions">
            <x-filament::badge :color="$item->panel ? 'primary' : 'gray'">
                {{ $item->type }}
            </x-filament::badge>
            <x-filament::icon-button
                icon="heroicon-m-pencil-square"
                size="sm"
                wire:click="mountAction('edit', {{ Js::from(['id' => $item->getKey(), 'title' => $item->resolveLocale($item->title)]) }})"
            />
            <x-filament::icon-button
                icon="heroicon-s-trash"
                color="danger"
                size="sm"
                wire:click="mountAction('delete', {{ Js::from(['id' => $item->getKey(), 'title' => $item->resolveLocale($item->title)]) }})"
            />
        </div>
    </div>

    @if ($hasChildren)
        <ul
            x-collapse
            x-show="open"
            wire:key="{{ $item->getKey() }}.children"
            x-data="menuBuilder({ parentId: @js($item->getKey()) })"
            class="fi-fo-repeater-items grid fi-menu-builder-item-children"
        >
            @foreach ($item->children as $child)
                <x-filament-menu-builder::menu-item :item="$child" />
            @endforeach
        </ul>
    @endif
</li>
