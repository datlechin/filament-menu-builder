<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Support;

use Closure;
use Datlechin\FilamentMenuBuilder\MenuBuilder;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

final class TranslatableField
{
    /**
     * @param  Closure(string $statePath): Field  $makeField
     */
    public static function make(string $model, string $attribute, Closure $makeField): Component
    {
        if (! in_array($attribute, MenuBuilder::translatableAttributes($model), true)) {
            return $makeField($attribute);
        }

        $locales = MenuBuilder::locales();

        return Tabs::make($attribute)
            ->tabs(array_map(
                fn (string $locale): Tab => Tab::make(strtoupper($locale))->schema([
                    tap($makeField("{$attribute}.{$locale}"), function (Field $field) use ($locale, $locales): void {
                        // Only the first locale is required, the others fall back to it.
                        if ($locale !== $locales[0]) {
                            $field->required(false);
                        }
                    }),
                ]),
                $locales,
            ))
            ->columnSpanFull();
    }
}
