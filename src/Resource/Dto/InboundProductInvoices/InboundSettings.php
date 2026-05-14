<?php

declare(strict_types=1);

namespace Nfe\Resource\Dto\InboundProductInvoices;

/**
 * Hand-written DTO for NF-e inbound auto-fetch settings (recebimento NF-e).
 *
 * Returned by `enableAutoFetch()`, `disableAutoFetch()`, and `getSettings()`
 * on {@see \Nfe\Resource\InboundProductInvoicesResource}.
 */
final readonly class InboundSettings
{
    /**
     * @param array<string, mixed>|null $raw
     */
    public function __construct(
        public ?bool $enabled = null,
        public ?string $federalTaxNumber = null,
        public ?string $environment = null,
        public ?array $raw = null,
    ) {}
}
