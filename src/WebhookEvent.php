<?php

declare(strict_types=1);

namespace Nfe;

/**
 * Parsed webhook event returned by {@see Webhook::constructEvent()}.
 *
 * The shape is deliberately minimal because NFE.io's webhook payloads vary
 * by product (NFS-e, NF-e, NFC-e, CT-e) and event type. The `data` field
 * carries the parsed JSON payload verbatim (after the v2 envelope unwrap),
 * so callers can branch on `$event->type` to extract product-specific fields.
 */
final readonly class WebhookEvent
{
    /**
     * @param string                $type      Event identifier (e.g., 'invoice.issued', or NFE.io's
     *                                          v2-envelope `action` value).
     * @param array<string, mixed>  $data      Parsed payload data (post-unwrap if v2 envelope present).
     * @param string|null           $id        Optional event ID, when the payload carries one.
     * @param string|null           $createdAt Optional ISO-8601 timestamp.
     */
    public function __construct(
        public string $type,
        public array $data,
        public ?string $id = null,
        public ?string $createdAt = null,
    ) {}
}
