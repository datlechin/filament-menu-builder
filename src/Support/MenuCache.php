<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Support;

use Closure;
use Datlechin\FilamentMenuBuilder\MenuBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class MenuCache
{
    public static function remember(string $key, ?string $variant, Closure $callback): mixed
    {
        $cache = MenuBuilder::cache();

        if (! $cache) {
            return $callback();
        }

        $version = $cache->rememberForever("{$key}.version", fn (): string => Str::random(12));

        return $cache->remember(
            $variant === null ? "{$key}.{$version}" : "{$key}.{$version}.{$variant}",
            config('filament-menu-builder.cache.ttl'),
            $callback,
        );
    }

    public static function forget(string $key): void
    {
        // A new version after the commit makes values built from the old data unreachable, even when a
        // request that read before the commit writes them afterwards.
        DB::afterCommit(fn () => MenuBuilder::cache()?->forget("{$key}.version"));
    }
}
