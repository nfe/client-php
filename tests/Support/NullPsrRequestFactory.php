<?php

declare(strict_types=1);

namespace Nfe\Tests\Support;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

/**
 * PSR-17 request factory that returns an inert {@see NullPsrRequest}.
 */
final class NullPsrRequestFactory implements RequestFactoryInterface
{
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new NullPsrRequest();
    }
}
