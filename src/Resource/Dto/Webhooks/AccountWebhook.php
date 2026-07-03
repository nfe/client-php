<?php

declare(strict_types=1);

namespace Nfe\Resource\Dto\Webhooks;

/**
 * Hand-written DTO for an account-scoped webhook subscription (`/v2/webhooks`).
 *
 * Mirrors the live wire shape confirmed against `api.nfe.io` (2026-07-02/03)
 * and the `/v2/webhooks` schema in `openapi/nf-servico-v1.yaml`. Wire-format
 * note: the OpenAPI spec declares `contentType` and `status` as int enums
 * (0/1), but the API serializes strings (`"json"`, `"Active"`/`"Inactive"`) —
 * this DTO follows the wire.
 *
 * Not to be confused with the {@see \Nfe\Webhook} static helper for signature
 * verification.
 */
final readonly class AccountWebhook
{
    /**
     * @param string|null $secret 32–64 chars; echoed on create, omitted on reads.
     * @param list<string>|null $filters Event-type filters (see `fetchEventTypes()`).
     * @param array<string, string>|null $headers Extra HTTP headers sent with deliveries.
     * @param array<string, mixed>|null $properties Extra properties merged into delivery bodies.
     * @param array<string, mixed>|null $raw Unwrapped response payload as received.
     */
    public function __construct(
        public ?string $id = null,
        public ?string $uri = null,
        public ?string $contentType = null,
        public ?string $secret = null,
        public ?array $filters = null,
        public ?bool $insecureSsl = null,
        public ?array $headers = null,
        public ?array $properties = null,
        public ?string $status = null,
        public ?string $createdOn = null,
        public ?string $modifiedOn = null,
        public ?array $raw = null,
    ) {}
}
