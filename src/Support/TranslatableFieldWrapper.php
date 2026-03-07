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
    public static function wrap(Field $field, array $locales): Tabs
    {
        $fieldName = $field->getName();

        $tabs = array_map(function (string $locale) use ($field, $locales, $fieldName): Tab {
            $clonedField = clone $field;
            $clonedField->name("{$fieldName}.{$locale}");
            $clonedField->statePath("{$fieldName}.{$locale}");
            $clonedField->label(null);

            if ($locale !== $locales[0]) {
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
