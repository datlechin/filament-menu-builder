<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\ItemTypes;

use Illuminate\Support\Str;

class UnregisteredItemType extends ItemType
{
    final public function __construct(protected string $name) {}

    public static function make(string $name): static
    {
        return new static($name);
    }

    public function getName(): string
    {
        return $this->name;
    }

    protected function getDefaultLabel(): string
    {
        return Str::headline($this->name);
    }
}
