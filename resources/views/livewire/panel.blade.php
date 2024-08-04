<form wire:submit="add">
    <x-filament::section
        :heading="$name"
        :collapsible="true"
        :persist-collapsed="true"
        id="{{ $id }}-panel"
    >
        {{ $this->form }}

        <x-slot:footerActions>
            <x-filament::button type="submit">
                {{ __('filament-menu-builder::menu-builder.add_to_menu') }}
            </x-filament::button>
        </x-slot:footerActions>
    </x-filament::section>
</form>
