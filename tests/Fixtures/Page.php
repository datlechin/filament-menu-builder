<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Tests\Fixtures;

use Datlechin\FilamentMenuBuilder\Concerns\HasMenuItems;
use Datlechin\FilamentMenuBuilder\Contracts\MenuLinkable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model implements MenuLinkable
{
    use HasMenuItems;
    use SoftDeletes;

    protected $guarded = [];

    public function getMenuTitle(): string
    {
        return $this->title;
    }

    public function getMenuUrl(): string
    {
        return route('pages.show', $this->slug);
    }
}
