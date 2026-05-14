<?php

declare(strict_types=1);

use Nfe\Exception\ApiErrorException;
use Nfe\Exception\AuthenticationException;
use Nfe\Exception\ErrorFactory;
use Nfe\Exception\InvalidRequestException;
use Nfe\Exception\NotFoundException;
use Nfe\Exception\RateLimitException;
use Nfe\Exception\ServerException;
use Nfe\Http\Response;

it('maps 400 to InvalidRequestException', function (): void {
    $e = ErrorFactory::fromResponse(new Response(400, [], '{"message":"bad input"}'));
    expect($e)->toBeInstanceOf(InvalidRequestException::class);
    expect($e->getMessage())->toBe('bad input');
    expect($e->statusCode)->toBe(400);
});

it('maps 401 to AuthenticationException', function (): void {
    $e = ErrorFactory::fromResponse(new Response(401, [], ''));
    expect($e)->toBeInstanceOf(AuthenticationException::class);
});

it('maps 404 to NotFoundException', function (): void {
    $e = ErrorFactory::fromResponse(new Response(404, [], '{"message":"not found"}'));
    expect($e)->toBeInstanceOf(NotFoundException::class);
});

it('maps 429 to RateLimitException', function (): void {
    $e = ErrorFactory::fromResponse(new Response(429, ['retry-after' => '10'], ''));
    expect($e)->toBeInstanceOf(RateLimitException::class);
});

it('maps 5xx to ServerException', function (): void {
    expect(ErrorFactory::fromResponse(new Response(500, [], '')))->toBeInstanceOf(ServerException::class);
    expect(ErrorFactory::fromResponse(new Response(502, [], '')))->toBeInstanceOf(ServerException::class);
    expect(ErrorFactory::fromResponse(new Response(599, [], '')))->toBeInstanceOf(ServerException::class);
});

it('exposes responseBody and responseHeaders', function (): void {
    $e = ErrorFactory::fromResponse(new Response(400, ['x-trace' => 'abc'], '{"errorCode":"E001","message":"oops"}'));
    expect($e)->toBeInstanceOf(ApiErrorException::class);
    expect($e->responseBody)->toBe('{"errorCode":"E001","message":"oops"}');
    expect($e->responseHeaders)->toBe(['x-trace' => 'abc']);
    expect($e->errorCode)->toBe('E001');
});

it('falls back to a generic message when body is empty', function (): void {
    $e = ErrorFactory::fromResponse(new Response(503, [], ''));
    expect($e->getMessage())->toBe('API request failed with HTTP 503');
});
