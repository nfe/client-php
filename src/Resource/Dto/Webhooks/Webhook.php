<?php

declare(strict_types=1);

namespace Nfe\Resource\Dto\Webhooks;

/**
 * Hand-written DTO for a webhook subscription.
 *
 * Not to be confused with the {@see \Nfe\Webhook} static helper for signature
 * verification — this DTO represents a managed webhook record returned by the
 * API's CRUD endpoints.
 */
final readonly class Webhook
{
    /**
     * @param list<string>|null $events
     * @param array<string, mixed>|null $raw
     */
    public function __construct(
        public ?string $id = null,
        public ?string $url = null,
        public ?array $events = null,
        public ?string $secret = null,
        public ?string $createdOn = null,
        public ?string $modifiedOn = null,
        public ?array $raw = null,
    ) {}
}
