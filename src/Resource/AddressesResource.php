<?php

declare(strict_types=1);

namespace Nfe\Resource;

use Nfe\Exception\InvalidRequestException;
use Nfe\Http\RequestOptions;
use Nfe\Resource\Dto\Addresses\AddressLookupResponse;
use Nfe\Util\IdValidator;

/**
 * Postal code (CEP), search, and term-based address lookup.
 *
 * Hosted at `https://address.api.nfe.io/v2` — version is embedded in the
 * base URL, so `apiVersion()` returns the empty string and `AbstractResource::fullPath()`
 * omits the version segment.
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
     * @param array<string, mixed> $opts Pass `filter` (OData $filter expression) to search by criteria.
     */
    public function search(array $opts = [], ?RequestOptions $options = null): AddressLookupResponse
    {
        $query = [];
        if (isset($opts['filter']) && is_string($opts['filter'])) {
            $query['$filter'] = $opts['filter'];
        }
        $response = $this->httpGet('/addresses', $query, $options);
        $payload = $this->decodeBody($response->body);

        return new AddressLookupResponse(
            addresses: $this->extractAddresses($payload),
            raw: $payload,
        );
    }

    public function lookupByTerm(string $term, ?RequestOptions $options = null): AddressLookupResponse
    {
        $trimmed = trim($term);
        if ($trimmed === '') {
            throw new InvalidRequestException('Termo de busca não pode ser vazio.');
        }
        $encoded = rawurlencode($trimmed);
        $response = $this->httpGet("/addresses/{$encoded}", options: $options);
        $payload = $this->decodeBody($response->body);

        return new AddressLookupResponse(
            addresses: $this->extractAddresses($payload),
            raw: $payload,
        );
    }

    /**
     * @param array<string, mixed> $payload
     * @return list<array<string, mixed>>
     */
    private function extractAddresses(array $payload): array
    {
        if (isset($payload['addresses']) && is_array($payload['addresses'])) {
            /** @var list<array<string, mixed>> $list */
            $list = array_values(array_filter(
                $payload['addresses'],
                static fn(mixed $v): bool => is_array($v),
            ));
            return $list;
        }
        // Single-object response (lookupByPostalCode often returns a single address)
        return [$payload];
    }
}
