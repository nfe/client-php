<?php

declare(strict_types=1);

namespace Nfe\Http;

use Closure;
use Nfe\Exception\ApiConnectionException;

/**
 * Decorator that wraps any {@see Transport} with method-aware retry semantics.
 *
 * Retry is safe for idempotent methods (GET, PUT, DELETE, HEAD, OPTIONS) but not
 * for POST, which may create a resource (e.g. issue an NFS-e). The decision is
 * therefore made per method, status code, and — for network failures — the
 * failure phase reported by the transport:
 *
 *   | Method                        | 429   | 5xx   | ConnectionNotEstablished | RequestMaybeSent |
 *   |-------------------------------|-------|-------|--------------------------|------------------|
 *   | GET/PUT/DELETE/HEAD/OPTIONS   | retry | retry | retry                    | retry            |
 *   | POST                          | retry | no    | retry                    | no               |
 *   | POST + `Idempotency-Key`      | retry | retry | retry                    | retry            |
 *
 * A POST carrying an `Idempotency-Key` header is treated as idempotent (the
 * server is expected to dedupe) — this is the forward-compatible path for when
 * the API honors the header. A network failure with no classified phase is
 * treated conservatively as `RequestMaybeSent`.
 *
 * Honors the `Retry-After` response header when present (integer seconds only;
 * HTTP-date form is not supported in v3.0).
 */
final class RetryingTransport implements Transport
{
    /**
     * HTTP methods safe to retry unconditionally (idempotent per RFC 9110).
     *
     * @var list<string>
     */
    private const IDEMPOTENT_METHODS = ['GET', 'PUT', 'DELETE', 'HEAD', 'OPTIONS'];

    public function __construct(
        private readonly Transport $inner,
        private readonly RetryPolicy $policy,
        private readonly ?Closure $sleepFn = null,
    ) {}

    public function send(Request $request): Response
    {
        $policy = $request->retry ?? $this->policy;
        $idempotent = $this->isIdempotent($request);
        $attempt = 0;

        while (true) {
            try {
                $response = $this->inner->send($request);

                if (!$this->shouldRetryStatus($idempotent, $response->statusCode) || $attempt >= $policy->maxRetries) {
                    return $response;
                }

                $delay = $this->resolveDelay($attempt + 1, $response, $policy);
            } catch (ApiConnectionException $e) {
                if (!$this->shouldRetryConnectionFailure($idempotent, $e) || $attempt >= $policy->maxRetries) {
                    throw $e;
                }

                $delay = $policy->delayFor($attempt + 1);
            }

            $this->sleep($delay);
            $attempt++;
        }
    }

    /**
     * A request is retry-safe for the full transient set when its method is
     * idempotent, or when it is a POST carrying an `Idempotency-Key` header
     * (server-side dedupe makes the retry safe).
     */
    private function isIdempotent(Request $request): bool
    {
        if (in_array(strtoupper($request->method), self::IDEMPOTENT_METHODS, true)) {
            return true;
        }

        return $this->hasIdempotencyKey($request);
    }

    private function hasIdempotencyKey(Request $request): bool
    {
        foreach (array_keys($request->headers) as $name) {
            if (strcasecmp($name, 'Idempotency-Key') === 0) {
                return true;
            }
        }

        return false;
    }

    private function shouldRetryStatus(bool $idempotent, int $statusCode): bool
    {
        if ($statusCode === 429) {
            // Rate-limited requests are rejected before processing — safe to
            // retry even a non-idempotent POST.
            return true;
        }

        // 5xx may mean the server already processed the request; only retry when
        // the method is idempotent (or carries an Idempotency-Key).
        return $idempotent && $statusCode >= 500 && $statusCode <= 599;
    }

    private function shouldRetryConnectionFailure(bool $idempotent, ApiConnectionException $e): bool
    {
        if ($idempotent) {
            return true;
        }

        // Non-idempotent: only retry when the request provably never reached the
        // server. Unclassified failures are treated as possibly-sent.
        return $e->failurePhase === FailurePhase::ConnectionNotEstablished;
    }

    private function resolveDelay(int $attempt, Response $response, RetryPolicy $policy): float
    {
        $retryAfter = $response->header('retry-after');
        if ($retryAfter !== null && ctype_digit($retryAfter)) {
            return min((float) $retryAfter, $policy->maxDelay);
        }

        return $policy->delayFor($attempt);
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
