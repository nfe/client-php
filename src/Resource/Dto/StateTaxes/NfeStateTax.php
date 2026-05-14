<?php

declare(strict_types=1);

namespace Nfe\Resource\Dto\StateTaxes;

/**
 * State tax registration record (Inscrição Estadual / IE).
 */
final readonly class NfeStateTax
{
    /**
     * @param array<string, mixed>|null $raw
     */
    public function __construct(
        public ?string $id = null,
        public ?string $taxNumber = null,
        public ?int $serie = null,
        public ?int $number = null,
        public ?string $code = null,
        public ?string $environmentType = null,
        public ?string $status = null,
        public ?string $createdOn = null,
        public ?string $modifiedOn = null,
        public ?array $raw = null,
    ) {}
}
