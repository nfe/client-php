<?php

declare(strict_types=1);

namespace Nfe\Tests\Support;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * PSR-18 client that always throws a pre-supplied exception, letting tests drive
 * {@see \Nfe\Http\Psr18Transport}'s catch/classification branch deterministically.
 */
final class ThrowingPsr18Client implements ClientInterface
{
    public function __construct(private readonly ClientExceptionInterface $toThrow) {}

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        throw $this->toThrow;
    }
}
