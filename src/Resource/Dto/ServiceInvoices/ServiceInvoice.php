<?php

declare(strict_types=1);

namespace Nfe\Resource\Dto\ServiceInvoices;

/**
 * Hand-written DTO for a service invoice (NFS-e).
 *
 * The OpenAPI spec (`service-invoice-rtc-v1.yaml`) provides request types but
 * does not include a canonical response type for the materialised invoice. This
 * DTO captures the fields the Node SDK and the WHMCS module actively read.
 *
 * All fields are nullable because (a) the API omits some fields depending on
 * processing state and (b) we want hydration to tolerate partial payloads.
 */
final readonly class ServiceInvoice
{
    /**
     * @param array<string, mixed>|null $raw Original raw payload from the API
     *        (kept for forward-compat with fields not yet typed here).
     */
    public function __construct(
        public ?string $id = null,
        public ?string $status = null,
        public ?string $flowStatus = null,
        public ?string $flowMessage = null,
        public ?string $environment = null,
        public ?int $rpsNumber = null,
        public ?string $rpsSerialNumber = null,
        public ?string $issuedOn = null,
        public ?string $createdOn = null,
        public ?string $modifiedOn = null,
        public ?string $cancelledOn = null,
        public ?float $servicesAmount = null,
        public ?float $totalAmount = null,
        public ?array $raw = null,
    ) {}
}
