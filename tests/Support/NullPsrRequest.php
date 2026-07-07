<?php

declare(strict_types=1);

namespace Nfe\Tests\Support;

use LogicException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Inert PSR-7 request double for {@see \Nfe\Http\Psr18Transport} tests.
 *
 * The transport builds a request via the factory and hands it to the client;
 * in the failure-classification tests the client throws before the request is
 * inspected, so mutators return `$this` and accessors that are never reached
 * throw to catch accidental use.
 */
final class NullPsrRequest implements RequestInterface
{
    public function getProtocolVersion(): string
    {
        return '1.1';
    }

    public function withProtocolVersion(string $version): RequestInterface
    {
        return $this;
    }

    /** @return array<string, array<int, string>> */
    public function getHeaders(): array
    {
        return [];
    }

    public function hasHeader(string $name): bool
    {
        return false;
    }

    /** @return array<int, string> */
    public function getHeader(string $name): array
    {
        return [];
    }

    public function getHeaderLine(string $name): string
    {
        return '';
    }

    public function withHeader(string $name, mixed $value): RequestInterface
    {
        return $this;
    }

    public function withAddedHeader(string $name, mixed $value): RequestInterface
    {
        return $this;
    }

    public function withoutHeader(string $name): RequestInterface
    {
        return $this;
    }

    public function getBody(): StreamInterface
    {
        throw new LogicException('NullPsrRequest::getBody() should not be called in tests.');
    }

    public function withBody(StreamInterface $body): RequestInterface
    {
        return $this;
    }

    public function getRequestTarget(): string
    {
        return '/';
    }

    public function withRequestTarget(string $requestTarget): RequestInterface
    {
        return $this;
    }

    public function getMethod(): string
    {
        return 'GET';
    }

    public function withMethod(string $method): RequestInterface
    {
        return $this;
    }

    public function getUri(): UriInterface
    {
        throw new LogicException('NullPsrRequest::getUri() should not be called in tests.');
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface
    {
        return $this;
    }
}
