<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class MenuUrl implements ValidationRule
{
    private const ALLOWED_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    public static function isSafe(string $url): bool
    {
        // Browsers ignore whitespace and control characters inside a scheme, so `java\tscript:` still runs.
        $normalized = preg_replace('/[\x00-\x20\x7F]+/', '', $url);

        if (! preg_match('/^([a-z][a-z0-9+.\-]*):/i', $normalized, $matches)) {
            return true;
        }

        return in_array(strtolower($matches[1]), self::ALLOWED_SCHEMES, true);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && ! self::isSafe($value)) {
            $fail('filament-menu-builder::menu-builder.validation.url')->translate();
        }
    }
}
