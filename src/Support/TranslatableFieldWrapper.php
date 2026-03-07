<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Support;

use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class TranslatableFieldWrapper
{
    /**
     * @param  string[]  $locales
     */
    public static function wrap(Field $field, array $locales, ?string $primaryLocale = null): Tabs
    {
        $fieldName = $field->getName();
        $primaryLocale ??= $locales[0];

        $tabs = array_map(function (string $locale) use ($field, $primaryLocale, $fieldName): Tab {
            $clonedField = clone $field;
            $clonedField->name("{$fieldName}.{$locale}");
            $clonedField->statePath("{$fieldName}.{$locale}");
            $clonedField->label(null);

            if ($locale !== $primaryLocale) {
                $clonedField->required(false);
            }

            return Tab::make(strtoupper($locale))
                ->schema([$clonedField]);
        }, $locales);

        return Tabs::make($fieldName)
            ->tabs($tabs)
            ->columnSpanFull();
    }
}
