<?php

declare(strict_types=1);

namespace Nfe\Resource\Dto\Addresses;

/**
 * Resposta da consulta de endereço por CEP.
 *
 * A API real de `address.api.nfe.io/v2` devolve um único endereço envelopado
 * como `{ "address": { … } }`. Desembrulhamos esse objeto e o expomos em
 * `$addresses` como uma lista normalizada (tipicamente com 1 elemento) para
 * consumo uniforme; `$raw` mantém o payload original decodificado.
 */
final readonly class AddressLookupResponse
{
    /**
     * @param list<array<string, mixed>> $addresses
     * @param array<string, mixed>|null $raw
     */
    public function __construct(
        public array $addresses = [],
        public ?array $raw = null,
    ) {}
}
