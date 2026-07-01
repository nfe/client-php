<?php

declare(strict_types=1);

namespace Nfe\Resource\Dto\ProductInvoiceQuery;

/**
 * NF-e details returned by `productInvoiceQuery.retrieve(accessKey)`.
 *
 * Shape is opaque (varies per NF-e and per UF); we expose the raw payload
 * for callers to traverse. A typed shape can be added later once the
 * OpenAPI spec gen covers it.
 */
final readonly class ProductInvoiceDetails
{
    /**
     * @param array<string, mixed>|null $raw
     */
    public function __construct(
        public ?string $accessKey = null,
        public ?int $number = null,
        public ?int $serie = null,
        public ?string $status = null,
        public ?string $issuedOn = null,
        public ?array $raw = null,
    ) {}
}
