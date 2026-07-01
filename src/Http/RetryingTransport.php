<?php

declare(strict_types=1);

namespace Nfe\Http;

use Closure;
use Nfe\Exception\ApiConnectionException;

/**
 * Decorator that wraps any {@see Transport} with retry semantics.
 *
 * Retries on:
 *   - HTTP 429 (Too Many Requests)
 *   - HTTP 5xx (Server errors)
 *   - Network-level failures (ApiConnectionException from the inner transport)
 *
 * Honors the `Retry-After` response header when present (integer seconds only;
 * HTTP-date form is not supported in v3.0).
 */
final class RetryingTransport implements Transport
{
    public function __construct(
        private readonly Transport $inner,
        private readonly RetryPolicy $policy,
        private readonly ?Closure $sleepFn = null,
    ) {}

    public function send(Request $request): Response
    {
        $attempt = 0;
        $lastResponse = null;
        $lastException = null;

        while (true) {
            try {
                $response = $this->inner->send($request);

                if (!$this->shouldRetry($response->statusCode) || $attempt >= $this->policy->maxRetries) {
                    return $response;
                }

                $lastResponse = $response;
                $delay = $this->resolveDelay($attempt + 1, $response);
            } catch (ApiConnectionException $e) {
                if ($attempt >= $this->policy->maxRetries) {
                    throw $e;
                }

                $lastException = $e;
                $delay = $this->policy->delayFor($attempt + 1);
            }

            $this->sleep($delay);
            $attempt++;
        }
    }

    private function shouldRetry(int $statusCode): bool
    {
        return $statusCode === 429 || ($statusCode >= 500 && $statusCode <= 599);
    }

    private function resolveDelay(int $attempt, Response $response): float
    {
        $retryAfter = $response->header('retry-after');
        if ($retryAfter !== null && ctype_digit($retryAfter)) {
            return min((float) $retryAfter, $this->policy->maxDelay);
        }

        return $this->policy->delayFor($attempt);
    }

    private function sleep(float $seconds): void
    {
        if ($this->sleepFn !== null) {
            ($this->sleepFn)($seconds);
            return;
        }

        $micro = (int) round($seconds * 1_000_000);
        if ($micro > 0) {
            usleep($micro);
        }
    }
}
