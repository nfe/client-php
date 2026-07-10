<?php

declare(strict_types=1);

namespace Nfe\Resource\Dto\ServiceInvoices;

/**
 * Tomador (borrower) de uma NFS-e, como devolvido na resposta da API.
 *
 * `federalTaxNumber` é tipado como união `int|string|null` (não `?int`): o
 * tomador pode ser CPF ou CNPJ e, como o spec declara `integer` mas a hidratação
 * é strict-typed, uma string no fio não pode estourar `TypeError`. É um
 * alargamento deliberado vs. o `integer` do OpenAPI, pinado pelo teste de
 * alinhamento.
 *
 * O `provider` (prestador — objeto pesado) NÃO é tipado; fica acessível via
 * `ServiceInvoice::$raw['provider']`.
 */
final readonly class Borrower
{
    /**
     * @param array<string, mixed>|null $address Endereço do tomador (mantido como array
     *        nesta iteração — 11 campos, raramente lido de volta).
     * @param array<string, mixed>|null $raw Payload cru do tomador (forward-compat).
     */
    public function __construct(
        public ?string $name = null,
        public int|string|null $federalTaxNumber = null,
        public ?string $email = null,
        public ?string $phoneNumber = null,
        public ?string $id = null,
        public ?string $parentId = null,
        public ?array $address = null,
        public ?array $raw = null,
    ) {}
}
