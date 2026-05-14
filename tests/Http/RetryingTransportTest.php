<?php

declare(strict_types=1);

use Nfe\Exception\ApiConnectionException;
use Nfe\Http\Request;
use Nfe\Http\Response;
use Nfe\Http\RetryingTransport;
use Nfe\Http\RetryPolicy;
use Nfe\Tests\Support\MockTransport;

function buildRetryingTransport(MockTransport $inner, RetryPolicy $policy): RetryingTransport
{
    // Inject a no-op sleep so tests run instantly.
    return new RetryingTransport($inner, $policy, fn (float $seconds) => null);
}

it('returns the response unchanged on success', function () {
    $mock = (new MockTransport())->push(new Response(200, [], 'ok'));
    $rt = buildRetryingTransport($mock, new RetryPolicy(maxRetries: 3));

    $r = $rt->send(new Request('GET', 'https://api.nfe.io', '/v1/x'));

    expect($r->statusCode)->toBe(200);
    expect(count($mock->sent()))->toBe(1);
});

it('retries on 503 and returns the eventual 200', function () {
    $mock = (new MockTransport())
        ->push(new Response(503, [], 'gone'))
        ->push(new Response(503, [], 'gone'))
        ->push(new Response(200, [], 'ok'));

    $rt = buildRetryingTransport($mock, new RetryPolicy(maxRetries: 3, baseDelay: 0.001));

    $r = $rt->send(new Request('GET', 'https://api.nfe.io', '/v1/x'));

    expect($r->statusCode)->toBe(200);
    expect(count($mock->sent()))->toBe(3);
});

it('does not retry on 400', function () {
    $mock = (new MockTransport())->push(new Response(400, [], 'bad'));
    $rt = buildRetryingTransport($mock, new RetryPolicy(maxRetries: 3));

    $r = $rt->send(new Request('POST', 'https://api.nfe.io', '/v1/x'));

    expect($r->statusCode)->toBe(400);
    expect(count($mock->sent()))->toBe(1);
});

it('does retry on 429', function () {
    $mock = (new MockTransport())
        ->push(new Response(429, ['retry-after' => '0'], 'slow down'))
        ->push(new Response(200, [], 'ok'));

    $rt = buildRetryingTransport($mock, new RetryPolicy(maxRetries: 1, baseDelay: 0.001));

    $r = $rt->send(new Request('GET', 'https://api.nfe.io', '/v1/x'));

    expect($r->statusCode)->toBe(200);
    expect(count($mock->sent()))->toBe(2);
});

it('returns the last 503 once retries are exhausted', function () {
    $mock = (new MockTransport())
        ->push(new Response(503, [], 'fail 1'))
        ->push(new Response(503, [], 'fail 2'));

    $rt = buildRetryingTransport($mock, new RetryPolicy(maxRetries: 1, baseDelay: 0.001));

    $r = $rt->send(new Request('GET', 'https://api.nfe.io', '/v1/x'));

    expect($r->statusCode)->toBe(503);
    expect($r->body)->toBe('fail 2');
    expect(count($mock->sent()))->toBe(2);
});

it('rethrows ApiConnectionException after retries are exhausted', function () {
    $mock = (new MockTransport())
        ->push(new ApiConnectionException('network 1'))
        ->push(new ApiConnectionException('network 2'));

    $rt = buildRetryingTransport($mock, new RetryPolicy(maxRetries: 1, baseDelay: 0.001));

    expect(fn () => $rt->send(new Request('GET', 'https://api.nfe.io', '/v1/x')))
        ->toThrow(ApiConnectionException::class, 'network 2');
});
