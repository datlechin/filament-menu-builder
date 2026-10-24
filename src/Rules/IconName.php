<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Rules;

use BladeUI\Icons\Exceptions\SvgNotFound;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class IconName implements ValidationRule
{
    public static function exists(string $name): bool
    {
        try {
            svg($name);

            return true;
        } catch (SvgNotFound) {
            return false;
        }
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && ! self::exists($value)) {
            $fail('filament-menu-builder::menu-builder.validation.icon')->translate();
        }
    }
}
