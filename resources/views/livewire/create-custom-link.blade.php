<form wire:submit="save">
    <x-filament::section
        heading="Liên kết tùy chỉnh"
        :collapsible="true"
        :persist-collapsed="true"
        id="create-custom-link"
    >
        {{ $this->form }}

        <x-slot:footerActions>
            <x-filament::button type="submit">
                Thêm vào menu
            </x-filament::button>
        </x-slot:footerActions>
    </x-filament::section>
</form>
