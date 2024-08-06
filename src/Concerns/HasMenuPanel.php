<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasMenuPanel
{
    public function getMenuPanelName(): string
    {
        return str($this->getTable())
            ->title()
            ->toString();
    }

    public function getMenuPanelModifyQueryUsing(): callable
    {
        return fn (Builder $query) => $query;
    }
}
