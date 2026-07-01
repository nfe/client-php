<?php

declare(strict_types=1);

namespace Nfe\Http;

/**
 * Per-request overrides applied on top of the SDK's global Config.
 *
 * Resource methods accept an optional RequestOptions to let callers override
 * the API key, base URL, or timeout for a single call without rebuilding
 * the Client. This is useful for multi-tenant integrations and for ad-hoc
 * calls against the sandbox from production code.
 *
 * Note on Idempotency-Key: the NFE.io API does not honor an Idempotency-Key
 * header today (confirmed 2026-05-13). When the API adds support, an additive
 * minor release will add an `idempotencyKey` field here.
 */
final readonly class RequestOptions
{
    public function __construct(
        public ?string $apiKey = null,
        public ?string $baseUrl = null,
        public ?int $timeout = null,
    ) {}
}
