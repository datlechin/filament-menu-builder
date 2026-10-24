<div
    x-data
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('menu-builder', package: 'datlechin/filament-menu-builder'))]"
    class="fi-menu-builder"
>
    <x-filament::section :heading="__('filament-menu-builder::menu-builder.items.heading')">
        @if ($this->canEdit())
            <x-slot name="afterHeader">
                {{ $this->getAddItemActions() }}
            </x-slot>
        @endif

        @if ($this->tree->isNotEmpty())
            <div class="fi-fo-repeater">
                <x-filament-menu-builder::tree.list
                    :items="$this->tree"
                    :hierarchy="$this->hierarchy"
                    :can-edit="$this->canEdit()"
                    x-load
                    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('menu-builder', 'datlechin/filament-menu-builder') }}"
                />
            </div>
        @else
            <x-filament::empty-state
                icon="heroicon-o-bars-3"
                :heading="__('filament-menu-builder::menu-builder.items.empty.heading')"
                :description="__('filament-menu-builder::menu-builder.items.empty.description')"
                :contained="false"
            />
        @endif
    </x-filament::section>

    <x-filament-actions::modals />
</div>
