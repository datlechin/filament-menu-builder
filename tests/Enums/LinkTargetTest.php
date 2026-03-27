<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Enums\LinkTarget;
use Filament\Support\Contracts\HasLabel;

it('has four cases', function () {
    expect(LinkTarget::cases())->toHaveCount(4);
});

it('has correct values', function () {
    expect(LinkTarget::Self->value)->toBe('_self')
        ->and(LinkTarget::Blank->value)->toBe('_blank')
        ->and(LinkTarget::Parent->value)->toBe('_parent')
        ->and(LinkTarget::Top->value)->toBe('_top');
});

it('implements HasLabel', function () {
    expect(LinkTarget::Self)->toBeInstanceOf(HasLabel::class);
});

it('returns labels for all cases', function () {
    foreach (LinkTarget::cases() as $case) {
        expect($case->getLabel())->toBeString()->not->toBeEmpty();
    }
});

it('can be created from value', function () {
    expect(LinkTarget::from('_self'))->toBe(LinkTarget::Self)
        ->and(LinkTarget::from('_blank'))->toBe(LinkTarget::Blank)
        ->and(LinkTarget::from('_parent'))->toBe(LinkTarget::Parent)
        ->and(LinkTarget::from('_top'))->toBe(LinkTarget::Top);
});

it('throws for invalid value', function () {
    LinkTarget::from('invalid');
})->throws(ValueError::class);
