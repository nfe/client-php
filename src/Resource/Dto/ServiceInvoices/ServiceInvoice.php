<?php

declare(strict_types=1);

namespace Nfe\Resource\Dto\ServiceInvoices;

/**
 * Hand-written DTO for a service invoice (NFS-e).
 *
 * The `nf-servico-v1.yaml` spec declares the response shape **inline** (no named,
 * reusable schema), so the codegen does not produce a model — this DTO is
 * maintained by hand. It is **deliberately partial**: the high-value fields a
 * consumer routinely reads are typed; everything else the API returns stays
 * reachable via {@see self::$raw} (always populated). A YAML↔DTO alignment test
 * pins the typed fields to the spec.
 *
 * All fields are nullable because (a) the API omits some fields depending on
 * processing state and (b) we want hydration to tolerate partial payloads.
 */
final readonly class ServiceInvoice
{
    /**
     * @param float|null $totalAmount @deprecated A API nunca retorna este campo (sempre `null`).
     *        Use `servicesAmount`/`amountNet`, ou `raw` para outros valores. Será removido na próxima major.
     * @param array<string, mixed>|null $raw Payload cru completo da API — escape-hatch para
     *        qualquer campo não tipado aqui (`$invoice->raw['field']`). Sempre populado.
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
        // Campos abaixo anexados ao fim do construtor: puramente aditivos
        // (a hidratação é por nome, então a posição é irrelevante para o SDK).
        public ?string $externalId = null,
        public ?int $number = null,
        public ?string $checkCode = null,
        public ?string $description = null,
        public ?string $cityServiceCode = null,
        public ?float $baseTaxAmount = null,
        public ?float $issRate = null,
        public ?float $issTaxAmount = null,
        public ?float $amountNet = null,
        public ?Borrower $borrower = null,
    ) {}
}
