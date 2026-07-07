<?php

declare(strict_types=1);

use Nfe\Client;
use Nfe\Config;
use Nfe\Http\RequestOptions;
use Nfe\Http\Response;
use Nfe\Http\RetryPolicy;
use Nfe\Tests\Support\MockTransport;

/**
 * Per-request retry override (add-safe-retry-idempotency, D4).
 *
 * These drive a real Client so the override is exercised end-to-end: it must
 * survive the Request rebuild in Client::send() and reach RetryingTransport.
 * GET is used so the method-aware rules never suppress a retry — isolating the
 * policy-selection behaviour under test.
 */

function clientWith(MockTransport $mock, RetryPolicy $retry): Client
{
    return new Client(config: new Config(apiKey: 'k', retry: $retry, transport: $mock));
}

it('disables retries for a single call on a retrying client', function (): void {
    $mock = (new MockTransport())
        ->push(new Response(503, [], 'fail'))
        ->push(new Response(200, [], '{"id":"x"}'));

    // Client retries by default, but this call opts out.
    $client = clientWith($mock, new RetryPolicy(maxRetries: 3, baseDelay: 0.001));

    expect(fn() => $client->serviceInvoices->retrieve(
        'abc',
        'inv-1',
        new RequestOptions(retry: RetryPolicy::none()),
    ))->toThrow(Nfe\Exception\ServerException::class);

    // Exactly one HTTP attempt — the 503 was surfaced, not retried.
    expect(count($mock->sent()))->toBe(1);
});

it('keeps client-level retries for other calls on the same client', function (): void {
    $mock = (new MockTransport())
        ->push(new Response(503, [], 'fail'))
        ->push(new Response(200, [], '{"id":"x"}'));

    $client = clientWith($mock, new RetryPolicy(maxRetries: 3, baseDelay: 0.001));

    // No override → client policy applies → retries past the 503.
    $invoice = $client->serviceInvoices->retrieve('abc', 'inv-1');

    expect($invoice->id)->toBe('x');
    expect(count($mock->sent()))->toBe(2);
});

it('enables retries for a single call on a zero-retry client', function (): void {
    $mock = (new MockTransport())
        ->push(new Response(503, [], 'fail'))
        ->push(new Response(200, [], '{"id":"x"}'));

    // Client has retries off globally...
    $client = clientWith($mock, RetryPolicy::none());

    // ...but this call turns them on.
    $invoice = $client->serviceInvoices->retrieve(
        'abc',
        'inv-1',
        new RequestOptions(retry: new RetryPolicy(maxRetries: 2, baseDelay: 0.001)),
    );

    expect($invoice->id)->toBe('x');
    expect(count($mock->sent()))->toBe(2);
});

it('makes exactly one attempt on a zero-retry client with no override', function (): void {
    $mock = (new MockTransport())
        ->push(new Response(503, [], 'fail'))
        ->push(new Response(200, [], '{"id":"x"}'));

    $client = clientWith($mock, RetryPolicy::none());

    expect(fn() => $client->serviceInvoices->retrieve('abc', 'inv-1'))
        ->toThrow(Nfe\Exception\ServerException::class);

    // Regression guard: the always-on decorator must not retry when policy is none().
    expect(count($mock->sent()))->toBe(1);
});
