<?php

declare(strict_types=1);

namespace Nfe\Resource\Dto\ConsumerInvoices;

/**
 * Hand-written DTO for a consumer invoice (NFC-e — Nota Fiscal de Consumidor
 * Eletrônica) emitida via API NFE.io.
 *
 * Shape mirrors the response of `POST /v2/companies/{id}/consumerinvoices`
 * and `GET /v2/companies/{id}/consumerinvoices/{id}` per `openapi/nf-consumidor-v2.yaml`.
 *
 * NOTA: a paridade com o Node SDK v3.2.0 deliberadamente NÃO expõe emissão
 * de NFC-e (eles cobrem apenas consulta via `consumerInvoiceQuery`). O PHP
 * SDK estende além da paridade Node porque a API NFE.io oferece o recurso
 * completo desde a v2 — decisão registrada em c05 (step 2 / post-mortem).
 */
final readonly class ConsumerInvoice
{
    /**
     * @param array<string, mixed>|null $raw Payload bruto preservado para forward-compat.
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
