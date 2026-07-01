<?php

declare(strict_types=1);

namespace Nfe\Exception;

/**
 * Raised when the SDK has exhausted retries against HTTP 429 responses.
 *
 * The internal retry policy already attempts to honor the `Retry-After` header.
 * Catching this exception means the limit persisted longer than the policy
 * allowed — back off further or surface the failure to the caller.
 */
final class RateLimitException extends ApiErrorException {}
