<form wire:submit="add">
    <x-filament::section
        :heading="$name"
        :collapsible="true"
        :persist-collapsed="true"
        id="{{ $id }}-panel"
    >
        {{ $this->form }}

        @if ($this->items)
            <x-slot:footerActions>
                <x-filament::button type="submit">
                    {{ __('filament-menu-builder::menu-builder.actions.add.label') }}
                </x-filament::button>
            </x-slot:footerActions>
        @endif
    </x-filament::section>
</form>
