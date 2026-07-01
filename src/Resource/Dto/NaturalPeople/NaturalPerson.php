<?php

declare(strict_types=1);

namespace Nfe\Resource\Dto\NaturalPeople;

/**
 * Hand-written DTO for a natural person (pessoa física / PF).
 */
final readonly class NaturalPerson
{
    /**
     * @param array<string, mixed>|null $address
     * @param array<string, mixed>|null $raw
     */
    public function __construct(
        public ?string $id = null,
        public ?string $federalTaxNumber = null,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $rg = null,
        public ?array $address = null,
        public ?string $createdOn = null,
        public ?string $modifiedOn = null,
        public ?array $raw = null,
    ) {}
}
