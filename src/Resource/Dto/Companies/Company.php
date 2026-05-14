<?php

declare(strict_types=1);

namespace Nfe\Resource\Dto\Companies;

/**
 * Hand-written DTO for a company (issuer of invoices).
 *
 * The Node SDK exposes a similar `Company` interface; the v2 OpenAPI specs
 * don't include a canonical Company schema, so this DTO is hand-curated.
 */
final readonly class Company
{
    /**
     * @param array<string, mixed>|null $raw
     */
    public function __construct(
        public ?string $id = null,
        public ?string $name = null,
        public ?string $tradeName = null,
        public ?int $federalTaxNumber = null,
        public ?string $email = null,
        public ?string $environment = null,
        public ?string $municipalTaxNumber = null,
        public ?string $regionalTaxNumber = null,
        public ?string $taxRegime = null,
        public ?string $createdOn = null,
        public ?string $modifiedOn = null,
        public ?array $raw = null,
    ) {}
}
