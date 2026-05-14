<?php

declare(strict_types=1);

namespace Nfe\Http;

/**
 * Contract for HTTP transports used by the SDK.
 *
 * Implementations:
 *   - {@see CurlTransport}  zero-dependency default backed by ext-curl
 *   - {@see Psr18Transport} adapter for any PSR-18 client (Guzzle, Symfony HttpClient, ...)
 *   - {@see RetryingTransport} decorator that wraps any other transport with retry semantics
 *
 * Implementations MUST:
 *   - Send the request unmodified except for header normalisation.
 *   - NEVER follow redirects or HTTP 202 Location headers automatically. The
 *     calling resource is responsible for that decision.
 *   - Return a {@see Response} with lowercase header keys.
 *   - Throw {@see \Nfe\Exception\ApiConnectionException} (or a subclass) on
 *     network-level failures (DNS, TLS, timeout). HTTP-level errors (4xx/5xx)
 *     are NOT thrown here; they are returned as a Response so the retry layer
 *     and exception factory can act on the status code.
 */
interface Transport
{
    public function send(Request $request): Response;
}
