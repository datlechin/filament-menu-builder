<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\ItemTypes;

use Closure;
use Datlechin\FilamentMenuBuilder\Contracts\MenuLinkable;
use Datlechin\FilamentMenuBuilder\MenuBuilder;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ModelItemType extends ItemType
{
    protected ?string $titleAttribute = null;

    protected ?Closure $modifyQueryUsing = null;

    /**
     * @param  class-string<Model&MenuLinkable>  $model
     */
    final public function __construct(protected string $model) {}

    /**
     * @param  class-string<Model&MenuLinkable>  $model
     */
    public static function make(string $model): static
    {
        return new static($model);
    }

    public function titleAttribute(?string $attribute): static
    {
        $this->titleAttribute = $attribute;

        return $this;
    }

    public function modifyQueryUsing(?Closure $callback): static
    {
        $this->modifyQueryUsing = $callback;

        return $this;
    }

    /**
     * @return class-string<Model&MenuLinkable>
     */
    public function getModel(): string
    {
        return $this->model;
    }

    public function getMorphClass(): string
    {
        return (new $this->model)->getMorphClass();
    }

    public function getName(): string
    {
        return 'model';
    }

    public function getKey(): string
    {
        return 'model:' . $this->getMorphClass();
    }

    protected function getDefaultLabel(): string
    {
        return Str::headline(Str::plural(class_basename($this->model)));
    }

    protected function getDefaultIcon(): string
    {
        return 'heroicon-o-document-text';
    }

    public function matches(MenuItem $item): bool
    {
        return $item->type === $this->getName() && $item->linkable_type === $this->getMorphClass();
    }

    public function getQuery(): Builder
    {
        $query = $this->model::query();

        if ($this->modifyQueryUsing) {
            $query = ($this->modifyQueryUsing)($query) ?? $query;
        }

        return $query;
    }

    public function getDestinationSchema(): array
    {
        return [
            $this->makeRecordSelect('linkable_id')->required(),
        ];
    }

    public function getCreateSchema(): array
    {
        return [
            $this->makeRecordSelect('records')->multiple()->required(),
        ];
    }

    public function getItemsToCreate(array $data): array
    {
        $titleIsTranslatable = in_array('title', MenuBuilder::translatableAttributes('menu_item'), true);

        return $this->getQuery()
            ->whereKey($data['records'])
            ->get()
            ->sortBy(fn (Model $record): int | false => array_search($record->getKey(), $data['records']))
            ->map(fn (Model & MenuLinkable $record): array => [
                ...Arr::except($data, 'records'),
                'title' => $titleIsTranslatable ? [MenuBuilder::locales()[0] => $record->getMenuTitle()] : $record->getMenuTitle(),
                'linkable_id' => $record->getKey(),
            ])
            ->values()
            ->all();
    }

    public function getAttributes(): array
    {
        return [
            'type' => $this->getName(),
            'linkable_type' => $this->getMorphClass(),
        ];
    }

    protected function makeRecordSelect(string $name): Select
    {
        $select = Select::make($name)
            ->label($this->getLabel())
            ->searchable()
            ->getOptionLabelUsing(fn (int | string $value): ?string => $this->getQuery()->find($value)?->getMenuTitle())
            ->getOptionLabelsUsing(fn (array $values): array => $this->toOptions($this->getQuery()->whereKey($values)->get()));

        if ($this->titleAttribute === null) {
            return $select->options(fn (): array => $this->toOptions($this->getQuery()->get()));
        }

        return $select
            ->options(fn (): array => $this->toOptions($this->getQuery()->orderBy($this->titleAttribute)->limit(50)->get()))
            ->getSearchResultsUsing(fn (string $search): array => $this->toOptions(
                $this->getQuery()->where($this->titleAttribute, 'like', "%{$search}%")->orderBy($this->titleAttribute)->limit(50)->get(),
            ));
    }

    /**
     * @param  iterable<Model&MenuLinkable>  $records
     * @return array<int|string, string>
     */
    protected function toOptions(iterable $records): array
    {
        return collect($records)->mapWithKeys(fn (Model & MenuLinkable $record): array => [$record->getKey() => $record->getMenuTitle()])->all();
    }
}
