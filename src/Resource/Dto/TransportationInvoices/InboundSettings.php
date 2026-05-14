<?php

declare(strict_types=1);

namespace Nfe\Resource\Dto\TransportationInvoices;

/**
 * Hand-written DTO for CT-e inbound auto-fetch settings.
 *
 * Returned by `enable()`, `disable()`, and `getSettings()` on
 * {@see \Nfe\Resource\TransportationInvoicesResource}.
 */
final readonly class InboundSettings
{
    /**
     * @param array<string, mixed>|null $raw
     */
    public function __construct(
        public ?bool $enabled = null,
        public ?string $federalTaxNumber = null,
        public ?array $raw = null,
    ) {}
}
