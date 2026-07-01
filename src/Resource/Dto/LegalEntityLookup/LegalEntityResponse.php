<?php

declare(strict_types=1);

namespace Nfe\Resource\Dto\LegalEntityLookup;

/**
 * Generic legal-entity lookup response (basicInfo, stateTax variants).
 *
 * Carries the raw `legalEntity` payload as an array because the shape varies
 * per endpoint (basicInfo vs stateTaxInfo vs stateTaxForInvoice). Use the
 * raw access for typed traversal until/unless we generate more specific DTOs.
 */
final readonly class LegalEntityResponse
{
    /**
     * @param array<string, mixed>|null $legalEntity
     * @param array<string, mixed>|null $raw
     */
    public function __construct(
        public ?array $legalEntity = null,
        public ?array $raw = null,
    ) {}
}
