<?php

declare(strict_types=1);

namespace Nfe\Http;

/**
 * Configuration for retry behavior on transient failures.
 *
 * The policy is applied by {@see RetryingTransport} as a decorator over any
 * other {@see Transport}. Retries trigger on:
 *   - HTTP 429 (Too Many Requests)
 *   - HTTP 5xx (Server errors)
 *   - Network-level failures wrapped in ApiConnectionException
 *
 * Delays follow exponential backoff with bounded jitter to avoid thundering herd:
 *
 *     delay(n) = min(maxDelay, baseDelay * 2^n) * (1 - jitter + 2 * jitter * rand())
 *
 * If the server provides a `Retry-After` header, that value takes precedence
 * over the computed delay (clamped to maxDelay).
 */
final readonly class RetryPolicy
{
    /**
     * @param int   $maxRetries Maximum number of *additional* attempts beyond the first request.
     *                          A value of 3 means up to 4 total HTTP calls.
     * @param float $baseDelay  Initial backoff in seconds.
     * @param float $maxDelay   Hard cap on a single delay between attempts, in seconds.
     * @param float $jitter     Symmetric jitter factor in [0.0, 1.0]. 0.3 = ±30%.
     */
    public function __construct(
        public int $maxRetries = 3,
        public float $baseDelay = 1.0,
        public float $maxDelay = 30.0,
        public float $jitter = 0.3,
    ) {}

    public static function default(): self
    {
        return new self();
    }

    public static function none(): self
    {
        return new self(maxRetries: 0);
    }

    /**
     * Compute the delay (in seconds) before attempt number $attempt
     * (1 = first retry, i.e. second request overall).
     */
    public function delayFor(int $attempt): float
    {
        $base = $this->baseDelay * (2 ** ($attempt - 1));
        $base = min($base, $this->maxDelay);

        if ($this->jitter <= 0.0) {
            return $base;
        }

        $multiplier = 1.0 - $this->jitter + (2.0 * $this->jitter * (mt_rand() / getrandmax()));

        return min($this->maxDelay, $base * $multiplier);
    }
}
