<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Concerns;

use Datlechin\FilamentMenuBuilder\MenuBuilder;
use Spatie\Translatable\HasTranslations;

trait HasTranslatableAttributes
{
    abstract protected static function translationsConfigKey(): string;

    public function initializeHasTranslatableAttributes(): void
    {
        if ($this->usesSpatieTranslations()) {
            return;
        }

        $this->mergeCasts(array_fill_keys(MenuBuilder::translatableAttributes(static::translationsConfigKey()), 'array'));
    }

    /**
     * @return list<string>
     */
    public function getTranslatedAttributeNames(): array
    {
        if ($this->usesSpatieTranslations()) {
            return $this->getTranslatableAttributes();
        }

        return MenuBuilder::translatableAttributes(static::translationsConfigKey());
    }

    public function isTranslatedAttribute(string $attribute): bool
    {
        return in_array($attribute, $this->getTranslatedAttributeNames(), true);
    }

    /**
     * @return array<string, string>
     */
    public function getTranslationsOf(string $attribute): array
    {
        $value = $this->getAttributes()[$attribute] ?? null;

        if (! is_string($value) || ! $this->isTranslatedAttribute($attribute)) {
            return [];
        }

        $translations = json_decode($value, true);

        return is_array($translations) ? array_filter($translations, filled(...)) : [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function withTranslations(array $data): array
    {
        foreach ($this->getTranslatedAttributeNames() as $attribute) {
            $data[$attribute] = $this->getTranslationsOf($attribute);
        }

        return $data;
    }

    public function translated(string $attribute, ?string $locale = null): string
    {
        if (! $this->isTranslatedAttribute($attribute)) {
            return (string) $this->getAttribute($attribute);
        }

        $translations = $this->getTranslationsOf($attribute);

        return $translations[$locale ?? app()->getLocale()]
            ?? $translations[config('app.fallback_locale')]
            ?? reset($translations)
            ?: '';
    }

    protected function usesSpatieTranslations(): bool
    {
        return in_array(HasTranslations::class, class_uses_recursive($this), true);
    }
}
