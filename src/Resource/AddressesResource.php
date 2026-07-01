<?php

declare(strict_types=1);

namespace Nfe\Resource;

use Nfe\Http\RequestOptions;
use Nfe\Resource\Dto\Addresses\AddressLookupResponse;
use Nfe\Util\IdValidator;

/**
 * Consulta de endereço por CEP.
 *
 * Hosted at `https://address.api.nfe.io/v2` — version is embedded in the
 * base URL, so `apiVersion()` returns the empty string and `AbstractResource::fullPath()`
 * omits the version segment.
 *
 * O host `address.api.nfe.io/v2` suporta **apenas** consulta por CEP. Os endpoints
 * de busca (`GET /addresses`) e por termo (`GET /addresses/{termo}`) não existem
 * (retornam 404), portanto não são expostos aqui — paridade com o SDK Node.
 */
final class AddressesResource extends AbstractResource
{
    protected function apiFamily(): string
    {
        return 'addresses';
    }

    protected function apiVersion(): string
    {
        return '';
    }

    public function lookupByPostalCode(string $cep, ?RequestOptions $options = null): AddressLookupResponse
    {
        $cep = IdValidator::cep($cep);
        $response = $this->httpGet("/addresses/{$cep}", options: $options);
        $payload = $this->decodeBody($response->body);

        return new AddressLookupResponse(
            addresses: $this->extractAddresses($payload),
            raw: $payload,
        );
    }

    /**
     * A API real devolve um único endereço envelopado como `{ "address": { … } }`.
     * Desembrulhamos para que os campos do endereço fiquem legíveis diretamente
     * em `AddressLookupResponse::$addresses` (lista de 1 elemento).
     *
     * @param array<string, mixed> $payload
     * @return list<array<string, mixed>>
     */
    private function extractAddresses(array $payload): array
    {
        if (isset($payload['address']) && is_array($payload['address'])) {
            /** @var array<string, mixed> $address */
            $address = $payload['address'];
            return [$address];
        }
        // Tolera uma coleção `{ "addresses": [ … ] }`, caso a API venha a retorná-la.
        if (isset($payload['addresses']) && is_array($payload['addresses'])) {
            /** @var list<array<string, mixed>> $list */
            $list = array_values(array_filter(
                $payload['addresses'],
                static fn(mixed $v): bool => is_array($v),
            ));
            return $list;
        }
        return [];
    }
}
