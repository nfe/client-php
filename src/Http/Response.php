<?php

declare(strict_types=1);

namespace Nfe\Http;

/**
 * Immutable HTTP response value object.
 *
 * Headers are normalised to lowercase keys for predictable lookup
 * regardless of server casing.
 */
final readonly class Response
{
    /**
     * @param int                   $statusCode HTTP status code.
     * @param array<string, string> $headers    Lowercase header name => value. Multi-value headers are
     *                                          joined with ", " per RFC 7230 §3.2.2.
     * @param string                $body       Raw response body bytes.
     */
    public function __construct(
        public int $statusCode,
        public array $headers,
        public string $body,
    ) {}

    /**
     * Case-insensitive header lookup.
     */
    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    /**
     * True for 2xx status codes.
     */
    public function isSuccess(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
}
