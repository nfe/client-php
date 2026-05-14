<?php

declare(strict_types=1);

namespace Nfe\Http;

/**
 * Immutable HTTP request value object used throughout the SDK.
 *
 * The transport is responsible for serialising this into an actual wire request.
 * Resources construct {@see Request} instances declaratively; they never touch
 * cURL or any PSR-18 client directly.
 */
final readonly class Request
{
    /**
     * @param string                $method  Uppercase HTTP method (GET, POST, PUT, DELETE, PATCH).
     * @param string                $baseUrl Origin only (scheme + host), e.g. "https://api.nfe.io". No trailing slash.
     * @param string                $path    Path including leading slash, e.g. "/v1/companies".
     * @param array<string, string> $headers Header name => value. Header names are kept as-given here and
     *                                       normalised by the transport. The transport may inject defaults
     *                                       (User-Agent, Authorization) when absent.
     * @param array<string, scalar|array<int, scalar>> $query   Query parameters. Arrays are sent as repeated keys.
     * @param string|null           $body    Pre-encoded request body. JSON-encoding is the caller's responsibility.
     * @param int                   $timeout Per-request timeout in seconds. 0 = use transport default.
     */
    public function __construct(
        public string $method,
        public string $baseUrl,
        public string $path,
        public array $headers = [],
        public array $query = [],
        public ?string $body = null,
        public int $timeout = 0,
    ) {}

    /**
     * Compose the final URL (baseUrl + path + query string).
     */
    public function url(): string
    {
        $url = rtrim($this->baseUrl, '/') . $this->path;

        if ($this->query !== []) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($this->query);
        }

        return $url;
    }
}
