@props([
    'item',
    'hierarchy',
    'canEdit',
])

@php
    $key = $item->getKey();
    $type = $this->getItemTypeFor($item);
    $url = $item->getUrl();
    $isBroken = $url === null && $item->hasDestination();
    $root = url('/');
    // Links to this site show their path, since the host is the same for every item.
    $displayUrl = $url !== null && ($url === $root || str_starts_with($url, "{$root}/"))
        ? (substr($url, strlen($root)) ?: '/')
        : $url;
    $hasChildren = $item->children->isNotEmpty();
    // Icons saved before they were validated may no longer exist, and rendering them would fail the whole page.
    $icon = filled($item->icon) && \Datlechin\FilamentMenuBuilder\Rules\IconName::exists($item->icon) ? $item->icon : null;
@endphp

<li
    wire:key="menu-item-{{ $key }}"
    data-sortable-item="{{ $key }}"
    data-sortable-height="{{ $hierarchy->heightOf($key) }}"
    x-data="{ isOpen: $persist(true).as('menu-builder-item-' + @js($key)) }"
    class="fi-fo-repeater-item fi-fo-repeater-item-has-header"
>
    <div class="fi-fo-repeater-item-header fi-menu-builder-item-header">
        <div class="fi-fo-repeater-item-header-start-actions">
            @if ($canEdit)
                <x-filament::icon-button
                    icon="heroicon-m-arrows-up-down"
                    color="gray"
                    size="sm"
                    :label="__('filament-menu-builder::menu-builder.actions.reorder')"
                    data-sortable-handle
                    class="fi-menu-builder-item-handle"
                />
            @endif

            @if ($hasChildren)
                <x-filament::icon-button
                    icon="heroicon-m-chevron-right"
                    color="gray"
                    size="sm"
                    :label="__('filament-menu-builder::menu-builder.items.toggle')"
                    x-on:click="isOpen = ! isOpen"
                    x-bind:aria-expanded="isOpen"
                    x-bind:class="{ 'fi-menu-builder-item-toggle-open': isOpen }"
                    class="fi-menu-builder-item-toggle"
                />
            @endif
        </div>

        <div class="fi-fo-repeater-item-header-label fi-menu-builder-item-label">
            @if ($icon)
                <x-filament::icon :icon="$icon" class="fi-menu-builder-item-label-icon" />
            @endif

            <span class="fi-menu-builder-item-label-text">
                {{ $item->translated('title') }}

                @if (filled($displayUrl))
                    <span class="fi-menu-builder-item-url">{{ $displayUrl }}</span>
                @endif
            </span>
        </div>

        <div class="fi-fo-repeater-item-header-end-actions">
            <div class="fi-menu-builder-item-badges">
                @if (! $item->is_visible)
                    <x-filament::badge color="warning" icon="heroicon-m-eye-slash">
                        {{ __('filament-menu-builder::menu-builder.items.hidden') }}
                    </x-filament::badge>
                @endif

                @if ($isBroken)
                    <x-filament::badge color="danger" icon="heroicon-m-exclamation-triangle">
                        {{ __('filament-menu-builder::menu-builder.items.broken') }}
                    </x-filament::badge>
                @endif

                <x-filament::badge color="gray" :icon="$type->getIcon()">
                    {{ $type->getLabel() }}
                </x-filament::badge>
            </div>

            @if ($canEdit)
                <div class="fi-menu-builder-item-actions">
                    {{ ($this->editItemAction)(['item' => $key]) }}

                    {{ $this->getItemActions($key) }}
                </div>
            @endif
        </div>
    </div>

    @if ($hasChildren)
        <x-filament-menu-builder::tree.list
            :items="$item->children"
            :hierarchy="$hierarchy"
            :can-edit="$canEdit"
            :parent-id="$key"
            x-show="isOpen"
            x-collapse
            class="fi-menu-builder-item-children"
        />
    @endif
</li>
