<?php

declare(strict_types=1);

namespace Nfe\Exception;

use Nfe\Http\FailurePhase;
use Throwable;

/**
 * Raised for network-level failures: DNS resolution, TCP connect, TLS,
 * or read timeouts before any HTTP response is received.
 *
 * No status code is available for these failures (statusCode = 0).
 *
 * Carries structured classification of the failure phase ({@see FailurePhase})
 * so the retry layer can decide whether a non-idempotent method (POST) is safe
 * to reexecute. When the transport cannot classify the failure, `$failurePhase`
 * is null and the retry layer treats it conservatively (as if the request may
 * have been sent).
 */
final class ApiConnectionException extends ApiErrorException
{
    /**
     * @param array<string, string> $responseHeaders
     */
    public function __construct(
        string $message,
        int $statusCode = 0,
        ?string $responseBody = null,
        array $responseHeaders = [],
        ?string $errorCode = null,
        ?Throwable $previous = null,
        public readonly ?FailurePhase $failurePhase = null,
        public readonly ?int $curlErrno = null,
    ) {
        parent::__construct($message, $statusCode, $responseBody, $responseHeaders, $errorCode, $previous);
    }
}
