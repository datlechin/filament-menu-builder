<div>
    @if($this->menuItems->isNotEmpty())
        <div class="fi-fo-repeater">
            <ul
                x-load
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('menu-builder', 'datlechin/filament-menu-builder') }}"
                x-data="menuBuilder({ parentId: 0 })"
                class="fi-fo-repeater-items grid"
            >
                @foreach($this->menuItems as $menuItem)
                    <x-filament-menu-builder::menu-item
                        :item="$menuItem"
                    />
                @endforeach
            </ul>
        </div>
    @else
        <x-filament::empty-state
            icon="heroicon-o-document"
            :heading="trans('filament-menu-builder::menu-builder.items.empty.heading')"
        />
    @endif

    <x-filament-actions::modals />
</div>
