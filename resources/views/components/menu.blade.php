<nav {{ $attributes->merge(array_filter(['aria-label' => $label])) }}>
    @include('filament-menu-builder::components.menu.items', ['nodes' => $tree->nodes()])
</nav>
