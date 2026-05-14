<?php

declare(strict_types=1);

namespace Nfe\Resource\Dto\NaturalPersonLookup;

/**
 * CPF cadastral status response (Receita Federal lookup).
 *
 * Shape mirrors the Node SDK's `NaturalPersonStatusResponse` interface
 * (types.ts:1786-1797).
 */
final readonly class NaturalPersonStatus
{
    public function __construct(
        public ?string $name = null,
        public ?string $federalTaxNumber = null,
        public ?string $birthOn = null,
        public ?string $status = null,
        public ?string $createdOn = null,
    ) {}
}
