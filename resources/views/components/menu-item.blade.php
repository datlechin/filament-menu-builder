@props([
    'item',
    'hierarchy',
    'startActions' => [],
    'endActions' => [],
])

@php
    use Filament\Actions\Action;
    use Illuminate\Support\Js;

    /**
     * @var \Datlechin\FilamentMenuBuilder\Models\MenuItem $item
     * @var \Datlechin\FilamentMenuBuilder\Support\MenuHierarchy $hierarchy
     * @var array<Action> $startActions
     * @var array<Action> $endActions
     */

    $key = $item->getKey();
    $url = $item->url;
    $hasChildren = $item->children->isNotEmpty();

    $visibleActions = fn (array $actions): array => array_filter(
        array_map(fn (Action $action): Action => $action(['id' => $key])->record($item), $actions),
        fn (Action $action): bool => $action->isVisible(),
    );
@endphp

<li
    wire:key="{{ $key }}"
    data-sortable-item="{{ $key }}"
    data-sortable-height="{{ $hierarchy->heightOf($key) }}"
    x-data="{ open: $persist(true).as('menu-item-' + @js($key)) }"
    class="fi-fo-repeater-item"
>
    <div class="fi-fo-repeater-item-header fi-menu-builder-item-header">
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
                    color="gray"
                    size="sm"
                    x-on:click="open = ! open"
                    x-bind:title="open ? {{ Js::from(__('filament-menu-builder::menu-builder.items.collapse')) }} : {{ Js::from(__('filament-menu-builder::menu-builder.items.expand')) }}"
                    x-bind:class="{ 'rotate-90': open }"
                    class="transition duration-200"
                />
            @endif

            @foreach ($visibleActions($startActions) as $action)
                {{ $action }}
            @endforeach
        </div>

        <div class="fi-fo-repeater-item-header-label fi-menu-builder-item-label">
            @if ($item->icon)
                <x-filament::icon :icon="$item->icon" class="fi-menu-builder-item-label-icon" />
            @endif

            <span class="fi-menu-builder-item-label-text">
                {{ $item->resolveLocale($item->title) }}

                @if (filled($url))
                    <span class="fi-menu-builder-item-url">{{ $url }}</span>
                @endif
            </span>
        </div>

        <div class="fi-fo-repeater-item-header-end-actions">
            <x-filament::badge :color="$item->panel ? 'primary' : 'gray'">
                {{ $item->type }}
            </x-filament::badge>

            @foreach ($visibleActions($endActions) as $action)
                {{ $action }}
            @endforeach
        </div>
    </div>

    @if ($hasChildren)
        <x-filament-menu-builder::menu-item-list
            :items="$item->children"
            :hierarchy="$hierarchy"
            :parent-id="$key"
            :start-actions="$startActions"
            :end-actions="$endActions"
            x-collapse
            x-show="open"
            wire:key="{{ $key }}.children"
            class="fi-menu-builder-item-children"
        />
    @endif
</li>
