<?php

declare(strict_types=1);

use Nfe\Http\RetryPolicy;

it('uses defaults consistent with Stripe-PHP', function () {
    $p = RetryPolicy::default();
    expect($p->maxRetries)->toBe(3);
    expect($p->baseDelay)->toBe(1.0);
    expect($p->maxDelay)->toBe(30.0);
});

it('disables retries with none()', function () {
    expect(RetryPolicy::none()->maxRetries)->toBe(0);
});

it('caps delay at maxDelay', function () {
    $p = new RetryPolicy(maxRetries: 10, baseDelay: 1.0, maxDelay: 5.0, jitter: 0.0);
    expect($p->delayFor(1))->toBe(1.0);
    expect($p->delayFor(2))->toBe(2.0);
    expect($p->delayFor(3))->toBe(4.0);
    expect($p->delayFor(4))->toBe(5.0); // capped
    expect($p->delayFor(10))->toBe(5.0);
});

it('applies symmetric jitter inside (1-jitter, 1+jitter) of base', function () {
    $p = new RetryPolicy(maxRetries: 5, baseDelay: 10.0, maxDelay: 1000.0, jitter: 0.3);
    for ($i = 0; $i < 100; $i++) {
        $d = $p->delayFor(1);
        expect($d)->toBeGreaterThanOrEqual(7.0);
        expect($d)->toBeLessThanOrEqual(13.0);
    }
});
