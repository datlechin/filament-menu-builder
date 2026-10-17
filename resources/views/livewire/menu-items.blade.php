<div>
    @if($this->menuItems->isNotEmpty())
        <div class="fi-fo-repeater">
            <x-filament-menu-builder::menu-item-list
                :items="$this->menuItems"
                :hierarchy="$this->hierarchy"
                :start-actions="[$this->unindentAction, $this->indentAction]"
                :end-actions="[$this->editAction, $this->deleteAction]"
                x-load
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('menu-builder', 'datlechin/filament-menu-builder') }}"
            />
        </div>
    @else
        <x-filament::empty-state
            icon="heroicon-o-document"
            :heading="trans('filament-menu-builder::menu-builder.items.empty.heading')"
        />
    @endif

    <x-filament-actions::modals />
</div>
