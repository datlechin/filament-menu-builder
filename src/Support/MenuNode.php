<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Support;

use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 */
final class MenuNode implements Arrayable, JsonSerializable
{
    /**
     * @param  array<string, mixed>  $data
     * @param  list<MenuNode>  $children
     */
    public function __construct(
        public readonly int | string $id,
        public readonly string $type,
        public readonly string $title,
        public readonly ?string $url,
        public readonly ?string $target,
        public readonly ?string $rel,
        public readonly ?string $icon,
        public readonly ?string $classes,
        public readonly array $data,
        public readonly int $depth,
        public readonly array $children = [],
    ) {}

    /**
     * @param  list<MenuNode>  $children
     */
    public static function fromItem(MenuItem $item, ?string $url, ?string $locale, int $depth, array $children): self
    {
        return new self(
            id: $item->getKey(),
            type: $item->type,
            title: $item->translated('title', $locale),
            url: $url,
            target: $item->target,
            rel: $item->rel,
            icon: $item->icon,
            classes: $item->classes,
            data: $item->data ?? [],
            depth: $depth,
            children: $children,
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromCacheArray(array $attributes): self
    {
        return new self(...[
            ...$attributes,
            'children' => array_map(self::fromCacheArray(...), $attributes['children']),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toCacheArray(): array
    {
        return [
            ...get_object_vars($this),
            'children' => array_map(fn (self $child): array => $child->toCacheArray(), $this->children),
        ];
    }

    /**
     * @param  list<MenuNode>  $children
     */
    public function withChildren(array $children): self
    {
        return new self(...[...get_object_vars($this), 'children' => $children]);
    }

    public function hasChildren(): bool
    {
        return $this->children !== [];
    }

    public function isActive(?string $currentUrl = null): bool
    {
        $path = self::comparablePath($this->url);

        return $path !== null && $path === self::comparablePath($currentUrl ?? request()->url());
    }

    public function isActiveTrail(?string $currentUrl = null): bool
    {
        $currentUrl ??= request()->url();

        if ($this->isActive($currentUrl)) {
            return true;
        }

        foreach ($this->children as $child) {
            if ($child->isActiveTrail($currentUrl)) {
                return true;
            }
        }

        $path = self::comparablePath($this->url);
        $currentPath = self::comparablePath($currentUrl);

        // A section link such as /blog stays highlighted on /blog/my-post, but the home page does not.
        return $path !== null && $currentPath !== null && ! str_ends_with($path, '/') && str_starts_with($currentPath, $path . '/');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            ...get_object_vars($this),
            'is_active' => $this->isActive(),
            'is_active_trail' => $this->isActiveTrail(),
            'children' => array_map(fn (self $child): array => $child->toArray(), $this->children),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private static function comparablePath(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $parts = parse_url(url($url));

        if (! is_array($parts) || ! in_array($parts['scheme'] ?? null, ['http', 'https'], true)) {
            return null;
        }

        return strtolower($parts['host'] ?? '') . '/' . trim($parts['path'] ?? '', '/');
    }
}
