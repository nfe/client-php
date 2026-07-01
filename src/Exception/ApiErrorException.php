<?php

declare(strict_types=1);

namespace Nfe\Exception;

use RuntimeException;
use Throwable;

/**
 * Base class for every exception raised by the SDK in response to an API call.
 *
 * Subclasses map to specific failure modes (4xx, 429, 5xx, network, etc.).
 * Catch this class to handle all SDK-raised errors generically; catch a
 * subclass to react to a specific failure mode.
 *
 * @phpstan-consistent-constructor
 */
abstract class ApiErrorException extends RuntimeException
{
    /**
     * @param array<string, string> $responseHeaders
     */
    public function __construct(
        string $message,
        public readonly int $statusCode = 0,
        public readonly ?string $responseBody = null,
        public readonly array $responseHeaders = [],
        public readonly ?string $errorCode = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }
}
