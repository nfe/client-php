<?php

declare(strict_types=1);

namespace Nfe\Exception;

/**
 * Raised for network-level failures: DNS resolution, TCP connect, TLS,
 * or read timeouts before any HTTP response is received.
 *
 * No status code is available for these failures (statusCode = 0).
 */
final class ApiConnectionException extends ApiErrorException {}
