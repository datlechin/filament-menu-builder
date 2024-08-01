<form wire:submit="add">
    <x-filament::section
        :heading="$heading"
        :collapsible="true"
        :persist-collapsed="true"
        id="menu-panel"
    >
        {{ $this->form }}

        <x-slot:footerActions>
            <x-filament::button type="submit">
                Thêm vào menu
            </x-filament::button>
        </x-slot:footerActions>
    </x-filament::section>
</form>
