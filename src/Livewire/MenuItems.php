<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Livewire;

use Datlechin\FilamentMenuBuilder\Concerns\ManagesMenuItemHierarchy;
use Datlechin\FilamentMenuBuilder\Enums\LinkTarget;
use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Support\TranslatableFieldWrapper;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class MenuItems extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use ManagesMenuItemHierarchy;

    public Menu $menu;

    #[Computed]
    #[On('menu:created')]
    public function menuItems(): Collection
    {
        return $this->menu->menuItems;
    }

    public function reorder(array $order, ?string $parentId = null): void
    {
        $this->getMenuItemService()->updateOrder($order, $parentId);
    }

    public function editAction(): Action
    {
        return Action::make('edit')
            ->label('Edit')
            ->iconButton()
            ->icon('heroicon-m-pencil-square')
            ->slideOver()
            ->modalWidth(Width::Medium)
            ->record(fn (array $arguments) => $this->getMenuItemService()->findByIdWithRelations($arguments['id']))
            ->fillForm(fn (array $arguments): array => $this->getMenuItemService()->findByIdWithRelations($arguments['id'])->toArray())
            ->form(fn (): array => $this->getEditFormSchema())
            ->action(fn (array $data, array $arguments) => $this->getMenuItemService()->update($arguments['id'], $data));
    }

    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->label('Delete')
            ->color('danger')
            ->icon('heroicon-s-trash')
            ->iconButton()
            ->requiresConfirmation()
            ->action(function (array $arguments): void {
                $this->getMenuItemService()->delete($arguments['id']);
            });
    }

    public function render(): View
    {
        return view('filament-menu-builder::livewire.menu-items');
    }

    protected function getEditFormSchema(): array
    {
        $plugin = FilamentMenuBuilderPlugin::get();

        $titleField = TextInput::make('title')
            ->label(__('filament-menu-builder::menu-builder.form.title'))
            ->required();

        $urlField = TextInput::make('url')
            ->hidden(fn (?string $state, Get $get): bool => blank($state) || filled($get('linkable_type')))
            ->label(__('filament-menu-builder::menu-builder.form.url'))
            ->required();

        if ($plugin->isTranslatable()) {
            $locales = $plugin->getTranslatableLocales();

            if (in_array('title', $plugin->getTranslatableMenuItemFields())) {
                $titleField = TranslatableFieldWrapper::wrap($titleField, $locales);
            }

            if (in_array('url', $plugin->getTranslatableMenuItemFields())) {
                $urlField = TranslatableFieldWrapper::wrap($urlField, $locales);
            }
        }

        $fields = [
            $titleField,
            $urlField,
            TextInput::make('linkable_type')
                ->label(__('filament-menu-builder::menu-builder.form.linkable_type'))
                ->hidden(fn (Get $get): bool => blank($get('linkable_type')))
                ->disabled()
                ->dehydrated(false),
            TextInput::make('linkable_id')
                ->label(__('filament-menu-builder::menu-builder.form.linkable_id'))
                ->hidden(fn (Get $get): bool => blank($get('linkable_id')))
                ->disabled()
                ->dehydrated(false),
            TextInput::make('icon')
                ->label(__('filament-menu-builder::menu-builder.form.icon'))
                ->placeholder('heroicon-o-home'),
            TextInput::make('classes')
                ->label(__('filament-menu-builder::menu-builder.form.classes'))
                ->placeholder('text-sm font-bold'),
            Select::make('target')
                ->label(__('filament-menu-builder::menu-builder.open_in.label'))
                ->options(LinkTarget::class)
                ->default(LinkTarget::Self),
        ];

        $customFields = FilamentMenuBuilderPlugin::get()->getMenuItemFields();

        if ($customFields instanceof \Closure) {
            $customFields = app()->call($customFields);
        }

        if (! empty($customFields)) {
            $fields[] = Group::make()
                ->schema($customFields);
        }

        return $fields;
    }
}
