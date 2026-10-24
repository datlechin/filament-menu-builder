<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Support\MenuNode;

function makeNode(?string $url, array $children = []): MenuNode
{
    return new MenuNode(
        id: 1,
        type: 'link',
        title: 'Item',
        url: $url,
        target: null,
        rel: null,
        icon: null,
        classes: null,
        data: [],
        depth: 0,
        children: $children,
    );
}

it('is active when its path matches the current URL', function (string $url) {
    expect(makeNode($url)->isActive('http://localhost/blog'))->toBeTrue();
})->with(['/blog', '/blog/', 'blog', 'http://localhost/blog', 'http://LOCALHOST/blog#top']);

it('is not active for another path or host', function (string $url) {
    expect(makeNode($url)->isActive('http://localhost/blog'))->toBeFalse();
})->with(['/', '/blog/post', 'https://example.com/blog']);

it('is never active for anchors, contact links or text items', function (?string $url) {
    expect(makeNode($url)->isActive('http://localhost'))->toBeFalse();
})->with(['#top', 'mailto:hi@example.com', 'tel:+1', null]);

it('is on the active trail when a descendant is active', function () {
    $node = makeNode('/products', [makeNode('/shoes', [makeNode('/shoes/running')])]);

    expect($node->isActiveTrail('http://localhost/shoes/running'))->toBeTrue()
        ->and($node->isActive('http://localhost/shoes/running'))->toBeFalse();
});

it('is on the active trail for pages below its path', function () {
    expect(makeNode('/blog')->isActiveTrail('http://localhost/blog/my-post'))->toBeTrue()
        ->and(makeNode('/blog')->isActiveTrail('http://localhost/blogging'))->toBeFalse()
        ->and(makeNode('/')->isActiveTrail('http://localhost/blog'))->toBeFalse();
});

it('round trips through its cache array', function () {
    $node = makeNode('/blog', [makeNode('/blog/news')]);

    expect(MenuNode::fromCacheArray($node->toCacheArray()))->toEqual($node);
});

it('serializes the active state of the current request', function () {
    $this->get('/blog');

    expect(makeNode('/blog')->toArray())
        ->toMatchArray(['url' => '/blog', 'is_active' => true, 'is_active_trail' => true, 'children' => []]);
});

it('only treats a link with a query as active on a page with the same values', function () {
    $node = makeNode('/products?category=shoes');

    expect($node->isActive('http://localhost/products?category=shoes&page=2'))->toBeTrue()
        ->and($node->isActive('http://localhost/products?category=hats'))->toBeFalse()
        ->and($node->isActive('http://localhost/products'))->toBeFalse()
        ->and(makeNode('/products')->isActive('http://localhost/products?category=hats'))->toBeTrue();
});
