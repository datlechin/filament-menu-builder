@props([
    'heading' => __('filament-menu-builder::menu-builder.panel.empty.heading'),
    'description' => __('filament-menu-builder::menu-builder.panel.empty.description'),
    'icon' => 'heroicon-o-link-slash',
])

<x-filament-tables::empty-state
    :heading="$heading"
    :description="$description"
    :icon="$icon"
/>
