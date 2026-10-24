<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Rules\MenuUrl;
use Illuminate\Support\Facades\Validator;

it('accepts web addresses, paths and contact links', function (string $url) {
    expect(MenuUrl::isSafe($url))->toBeTrue();
})->with([
    'https://example.com',
    'http://example.com/a?b=c',
    '/about',
    'about',
    '#section',
    '?page=2',
    '//cdn.example.com/file',
    'mailto:hi@example.com',
    'tel:+123456',
]);

it('rejects URLs that run code', function (string $url) {
    expect(MenuUrl::isSafe($url))->toBeFalse();
})->with([
    'javascript:alert(1)',
    'JavaScript:alert(1)',
    " java\tscript:alert(1)",
    "java\nscript:alert(1)",
    'data:text/html,<script>alert(1)</script>',
    'vbscript:msgbox(1)',
]);

it('fails validation for an unsafe URL', function () {
    $validator = Validator::make(['url' => 'javascript:alert(1)'], ['url' => [new MenuUrl]]);

    expect($validator->fails())->toBeTrue();
});
