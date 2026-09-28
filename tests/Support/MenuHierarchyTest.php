<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Support\MenuHierarchy;

/**
 * Tree used by most tests:
 *
 *  1
 *  ├─ 2
 *  │  └─ 3
 *  │     └─ 4
 *  └─ 5
 *  6
 */
function makeHierarchy(?int $maxDepth = null, ?array $rows = null): MenuHierarchy
{
    $rows ??= [[1, null], [2, 1], [3, 2], [4, 3], [5, 1], [6, null]];

    return new MenuHierarchy(
        array_map(fn (array $row): object => (object) ['id' => $row[0], 'parent_id' => $row[1]], $rows),
        $maxDepth,
    );
}

it('computes zero-based depth', function () {
    $hierarchy = makeHierarchy();

    expect($hierarchy->depthOf(1))->toBe(0)
        ->and($hierarchy->depthOf(2))->toBe(1)
        ->and($hierarchy->depthOf(4))->toBe(3)
        ->and($hierarchy->depthOf(6))->toBe(0);
});

it('computes subtree height', function () {
    $hierarchy = makeHierarchy();

    expect($hierarchy->heightOf(1))->toBe(3)
        ->and($hierarchy->heightOf(2))->toBe(2)
        ->and($hierarchy->heightOf(4))->toBe(0)
        ->and($hierarchy->heightOf(5))->toBe(0);
});

it('resolves parents and previous siblings', function () {
    $hierarchy = makeHierarchy();

    expect($hierarchy->parentOf(1))->toBeNull()
        ->and($hierarchy->parentOf(3))->toBe(2)
        ->and($hierarchy->previousSiblingOf(5))->toBe(2)
        ->and($hierarchy->previousSiblingOf(6))->toBe(1)
        ->and($hierarchy->previousSiblingOf(1))->toBeNull()
        ->and($hierarchy->previousSiblingOf(99))->toBeNull();
});

it('detects descendants', function () {
    $hierarchy = makeHierarchy();

    expect($hierarchy->isDescendantOf(4, 1))->toBeTrue()
        ->and($hierarchy->isDescendantOf(4, 2))->toBeTrue()
        ->and($hierarchy->isDescendantOf(5, 2))->toBeFalse()
        ->and($hierarchy->isDescendantOf(1, 1))->toBeFalse();
});

it('preserves original key types', function () {
    $hierarchy = makeHierarchy(rows: [['a-uuid', null], ['b-uuid', null]]);

    expect($hierarchy->previousSiblingOf('b-uuid'))->toBe('a-uuid')
        ->and($hierarchy->contains('a-uuid'))->toBeTrue();
});

it('ignores items that are not reachable from the root', function () {
    $hierarchy = makeHierarchy(rows: [[1, null], [2, 3], [3, 2], [4, 99]]);

    expect($hierarchy->contains(1))->toBeTrue()
        ->and($hierarchy->contains(2))->toBeFalse()
        ->and($hierarchy->contains(3))->toBeFalse()
        ->and($hierarchy->contains(4))->toBeFalse()
        ->and($hierarchy->canMove([2], 1))->toBeFalse()
        ->and($hierarchy->canMove([1], 3))->toBeFalse();
});

it('allows unlimited nesting without a max depth', function () {
    $hierarchy = makeHierarchy();

    expect($hierarchy->canIndent(6))->toBeTrue()
        ->and($hierarchy->canMove([6], 4))->toBeTrue();
});

it('prevents indenting beyond the max depth', function () {
    $hierarchy = makeHierarchy(maxDepth: 1);

    expect($hierarchy->canIndent(6))->toBeTrue() // 6 would land at depth 1
        ->and($hierarchy->canIndent(5))->toBeFalse() // 5 would land at depth 2
        ->and(makeHierarchy(maxDepth: 0)->canIndent(6))->toBeFalse();
});

it('accounts for the moved subtree height when indenting', function () {
    // 1, 2 ─ 3 : indenting 2 under 1 puts 3 at depth 2.
    $rows = [[1, null], [2, null], [3, 2]];

    expect(makeHierarchy(maxDepth: 1, rows: $rows)->canIndent(2))->toBeFalse()
        ->and(makeHierarchy(maxDepth: 2, rows: $rows)->canIndent(2))->toBeTrue();
});

it('always allows unindenting nested items', function () {
    $hierarchy = makeHierarchy(maxDepth: 0);

    expect($hierarchy->canUnindent(4))->toBeTrue()
        ->and($hierarchy->canUnindent(1))->toBeFalse()
        ->and($hierarchy->canUnindent(99))->toBeFalse();
});

it('rejects moves that exceed the max depth', function () {
    $hierarchy = makeHierarchy(maxDepth: 2);

    expect($hierarchy->canMove([6], 2))->toBeTrue()
        ->and($hierarchy->canMove([6], 3))->toBeFalse()
        ->and($hierarchy->canMove([2], 5))->toBeFalse()
        ->and($hierarchy->canMove([2], null))->toBeTrue();
});

it('allows reordering existing children of a parent that is already too deep', function () {
    $hierarchy = makeHierarchy(maxDepth: 1);

    expect($hierarchy->canMove([4], 3))->toBeTrue()
        ->and($hierarchy->canMove([2, 5], 1))->toBeTrue()
        ->and($hierarchy->canMove([6, 1], null))->toBeTrue();
});

it('rejects moving an item into itself or its descendants', function () {
    $hierarchy = makeHierarchy();

    expect($hierarchy->canMove([2], 2))->toBeFalse()
        ->and($hierarchy->canMove([2], 4))->toBeFalse()
        ->and($hierarchy->canMove([1], 3))->toBeFalse();
});

it('rejects unknown items and parents', function () {
    $hierarchy = makeHierarchy();

    expect($hierarchy->canMove([99], null))->toBeFalse()
        ->and($hierarchy->canMove([6], 99))->toBeFalse();
});

it('accepts string identifiers from the client', function () {
    expect(makeHierarchy(maxDepth: 1)->canMove(['6'], '1'))->toBeTrue();
});
