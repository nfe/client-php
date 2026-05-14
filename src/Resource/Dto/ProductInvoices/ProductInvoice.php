<?php

declare(strict_types=1);

namespace Nfe\Resource\Dto\ProductInvoices;

/**
 * Hand-written DTO for a product invoice (NF-e).
 *
 * Mirrors the Node SDK's `NfeProductInvoice` interface and complements the
 * generated `Nfe\Generated\ProductInvoiceRtcV1\*` types (which cover request
 * shapes but not a single canonical response DTO).
 */
final readonly class ProductInvoice
{
    /**
     * @param array<string, mixed>|null $raw
     */
    public function __construct(
        public ?string $id = null,
        public ?string $status = null,
        public ?string $flowStatus = null,
        public ?string $flowMessage = null,
        public ?string $environment = null,
        public ?string $accessKey = null,
        public ?int $number = null,
        public ?int $serie = null,
        public ?string $issuedOn = null,
        public ?string $createdOn = null,
        public ?string $modifiedOn = null,
        public ?string $cancelledOn = null,
        public ?float $totalAmount = null,
        public ?array $raw = null,
    ) {}
}
