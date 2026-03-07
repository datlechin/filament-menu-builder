<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Concerns;

trait ResolvesLocale
{
    public function resolveLocale(mixed $value): string
    {
        if (is_array($value)) {
            return $value[app()->getLocale()] ?? $value[array_key_first($value)] ?? '';
        }

        return (string) ($value ?? '');
    }
}
