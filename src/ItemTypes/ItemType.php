<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\ItemTypes;

use Closure;
use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Support\TranslatableField;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Support\Concerns\EvaluatesClosures;
use Illuminate\Database\Eloquent\Model;

abstract class ItemType
{
    use EvaluatesClosures;

    protected string | Closure | null $label = null;

    protected string | Closure | null $icon = null;

    protected bool | Closure $isVisible = true;

    abstract public function getName(): string;

    abstract protected function getDefaultLabel(): string;

    protected function getDefaultIcon(): ?string
    {
        return null;
    }

    public function getKey(): string
    {
        return $this->getName();
    }

    public function label(string | Closure | null $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->evaluate($this->label) ?? $this->getDefaultLabel();
    }

    public function icon(string | Closure | null $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->evaluate($this->icon) ?? $this->getDefaultIcon();
    }

    public function visible(bool | Closure $condition = true): static
    {
        $this->isVisible = $condition;

        return $this;
    }

    public function isVisible(Model $menu): bool
    {
        return (bool) $this->evaluate(
            $this->isVisible,
            namedInjections: ['menu' => $menu],
            typedInjections: [Model::class => $menu, Menu::class => $menu, $menu::class => $menu],
        );
    }

    public function matches(MenuItem $item): bool
    {
        return $item->type === $this->getName();
    }

    public function opensLink(): bool
    {
        return true;
    }

    /**
     * @return array<Component>
     */
    public function getFormSchema(): array
    {
        $advancedSchema = $this->getAdvancedSchema();

        return [
            TranslatableField::make('menu_item', 'title', fn (string $statePath): TextInput => TextInput::make($statePath)
                ->label(__('filament-menu-builder::menu-builder.form.title'))
                ->required()
                ->maxLength(255)),
            ...$this->getDestinationSchema(),
            Toggle::make('target')
                ->label(__('filament-menu-builder::menu-builder.form.open_in_new_tab'))
                ->visible($this->opensLink())
                ->formatStateUsing(fn (?string $state): bool => $state === '_blank')
                ->dehydrateStateUsing(fn (?bool $state): ?string => $state ? '_blank' : null),
            Section::make(__('filament-menu-builder::menu-builder.form.advanced'))
                ->collapsed()
                ->compact()
                ->visible($advancedSchema !== [])
                ->schema($advancedSchema),
            ...FilamentMenuBuilderPlugin::get()->getMenuItemFields($this),
            Toggle::make('is_visible')
                ->label(__('filament-menu-builder::menu-builder.form.is_visible'))
                ->default(true),
        ];
    }

    /**
     * @return array<Component>
     */
    public function getDestinationSchema(): array
    {
        return [];
    }

    /**
     * @return array<Component>
     */
    public function getAdvancedSchema(): array
    {
        return [
            TextInput::make('rel')
                ->label(__('filament-menu-builder::menu-builder.form.rel'))
                ->placeholder('nofollow noopener')
                ->visible($this->opensLink()),
            TextInput::make('icon')
                ->label(__('filament-menu-builder::menu-builder.form.icon'))
                ->placeholder('heroicon-o-home'),
            TextInput::make('classes')
                ->label(__('filament-menu-builder::menu-builder.form.classes')),
        ];
    }

    /**
     * @return array<Component>
     */
    public function getCreateSchema(): array
    {
        return $this->getFormSchema();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array<string, mixed>>
     */
    public function getItemsToCreate(array $data): array
    {
        return [$data];
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return ['type' => $this->getName()];
    }
}
