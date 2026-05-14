<?php

declare(strict_types=1);

use Nfe\Environment;

it('has exactly two cases', function (): void {
    expect(Environment::cases())->toHaveCount(2);
});

it('is string-backed with expected values', function (): void {
    expect(Environment::Production->value)->toBe('production');
    expect(Environment::Sandbox->value)->toBe('sandbox');
});
