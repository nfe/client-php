<?php

declare(strict_types=1);

namespace Nfe\Tests\Support;

use LogicException;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * PSR-17 stream factory whose methods throw — the failure-classification tests
 * drive GET requests with no body, so `createStream*()` is never reached.
 */
final class NullPsrStreamFactory implements StreamFactoryInterface
{
    public function createStream(string $content = ''): StreamInterface
    {
        throw new LogicException('NullPsrStreamFactory::createStream() should not be called in tests.');
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        throw new LogicException('NullPsrStreamFactory::createStreamFromFile() should not be called in tests.');
    }

    public function createStreamFromResource($resource): StreamInterface
    {
        throw new LogicException('NullPsrStreamFactory::createStreamFromResource() should not be called in tests.');
    }
}
