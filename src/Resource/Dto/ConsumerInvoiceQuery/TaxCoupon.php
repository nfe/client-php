<?php

declare(strict_types=1);

namespace Nfe\Resource\Dto\ConsumerInvoiceQuery;

/**
 * NFC-e tax coupon (cupom fiscal eletrônico).
 *
 * Shape mirrors the Node SDK's `TaxCoupon` interface (types.ts:1407-1426).
 * All fields are optional because (a) the API omits irrelevant fields
 * depending on the operation and (b) we want hydration to tolerate
 * partial payloads.
 */
final readonly class TaxCoupon
{
    /**
     * @param array<string, mixed>|null $issuer
     * @param array<string, mixed>|null $buyer
     * @param array<string, mixed>|null $totals
     * @param array<string, mixed>|null $delivery
     * @param array<string, mixed>|null $additionalInformation
     * @param list<array<string, mixed>>|null $items
     * @param array<string, mixed>|null $payment
     */
    public function __construct(
        public ?string $currentStatus = null,
        public ?int $number = null,
        public ?string $satSerie = null,
        public ?string $softwareVersion = null,
        public ?int $softwareFederalTaxNumber = null,
        public ?string $accessKey = null,
        public ?int $cashier = null,
        public ?string $issuedOn = null,
        public ?string $createdOn = null,
        public ?string $xmlVersion = null,
        public ?array $issuer = null,
        public ?array $buyer = null,
        public ?array $totals = null,
        public ?array $delivery = null,
        public ?array $additionalInformation = null,
        public ?array $items = null,
        public ?array $payment = null,
    ) {}
}
