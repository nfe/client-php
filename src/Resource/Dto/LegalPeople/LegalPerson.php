<?php

declare(strict_types=1);

namespace Nfe\Resource\Dto\LegalPeople;

/**
 * Hand-written DTO for a legal person (pessoa jurídica / PJ).
 */
final readonly class LegalPerson
{
    /**
     * @param array<string, mixed>|null $address
     * @param array<string, mixed>|null $raw
     */
    public function __construct(
        public ?string $id = null,
        public ?int $federalTaxNumber = null,
        public ?string $municipalTaxNumber = null,
        public ?string $name = null,
        public ?string $tradeName = null,
        public ?string $email = null,
        public ?array $address = null,
        public ?string $createdOn = null,
        public ?string $modifiedOn = null,
        public ?array $raw = null,
    ) {}
}
