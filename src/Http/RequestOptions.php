<?php

declare(strict_types=1);

namespace Nfe\Http;

/**
 * Per-request overrides applied on top of the SDK's global Config.
 *
 * Resource methods accept an optional RequestOptions to let callers override
 * the API key, base URL, timeout, or retry policy for a single call without
 * rebuilding the Client. This is useful for multi-tenant integrations, ad-hoc
 * calls against the sandbox from production code, and disabling retries on a
 * specific write while keeping them on for the rest of the client.
 *
 * Note on Idempotency-Key: the NFE.io API does not honor an Idempotency-Key
 * header today (reconfirmed via live probe on 2026-07-05). When the API adds
 * support, an additive minor release will add an `idempotencyKey` field here;
 * until then, attaching the header only unlocks retry-on-5xx for the POST (see
 * {@see RetryingTransport}) — it does not make the server dedupe. For safe NFS-e
 * emission today, send an `externalId` and reconcile with
 * `ServiceInvoicesResource::findByExternalId()` after an ambiguous failure.
 */
final readonly class RequestOptions
{
    public function __construct(
        public ?string $apiKey = null,
        public ?string $baseUrl = null,
        public ?int $timeout = null,
        public ?RetryPolicy $retry = null,
    ) {}
}
