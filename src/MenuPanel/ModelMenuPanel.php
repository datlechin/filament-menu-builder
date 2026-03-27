<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\MenuPanel;

use Closure;
use Datlechin\FilamentMenuBuilder\Contracts\MenuPanelable;
use Illuminate\Database\Eloquent\Model;

class ModelMenuPanel extends AbstractMenuPanel
{
    /**
     * @var Model&MenuPanelable
     */
    protected Model $model;

    protected Closure $urlUsing;

    /**
     * @param  class-string<Model&MenuPanelable>  $model
     */
    public function model(string $model): static
    {
        $this->model = new $model;

        return $this;
    }

    public function getName(): string
    {
        return $this->model->getMenuPanelName();
    }

    public function getItems(): array
    {
        return ($this->model->getMenuPanelModifyQueryUsing())($this->model->newQuery())
            ->get()
            ->map(fn (Model $model) => [
                'title' => $model->{$this->model->getMenuPanelTitleColumn()},
                'linkable_type' => $model->getMorphClass(),
                'linkable_id' => $model->getKey(),
            ])
            ->all();
    }
}
