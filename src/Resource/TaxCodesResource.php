<?php

declare(strict_types=1);

namespace Nfe\Resource;

use Nfe\Http\RequestOptions;
use Nfe\Resource\Dto\TaxCodes\TaxCodePaginatedResponse;

/**
 * Tax codes listings (operation codes, acquisition purposes, issuer/recipient
 * tax profiles).
 *
 * Pagination is page-style 1-based (`pageIndex`, `pageCount`).
 * Hosted at `https://api.nfse.io`.
 */
final class TaxCodesResource extends AbstractResource
{
    protected function apiFamily(): string
    {
        return 'tax-codes';
    }

    protected function apiVersion(): string
    {
        return '';
    }

    /**
     * @param array{pageIndex?: int, pageCount?: int} $opts
     */
    public function listOperationCodes(array $opts = [], ?RequestOptions $options = null): TaxCodePaginatedResponse
    {
        return $this->listEndpoint('/tax-codes/operation-code', $opts, $options);
    }

    /**
     * @param array{pageIndex?: int, pageCount?: int} $opts
     */
    public function listAcquisitionPurposes(array $opts = [], ?RequestOptions $options = null): TaxCodePaginatedResponse
    {
        return $this->listEndpoint('/tax-codes/acquisition-purpose', $opts, $options);
    }

    /**
     * @param array{pageIndex?: int, pageCount?: int} $opts
     */
    public function listIssuerTaxProfiles(array $opts = [], ?RequestOptions $options = null): TaxCodePaginatedResponse
    {
        return $this->listEndpoint('/tax-codes/issuer-tax-profile', $opts, $options);
    }

    /**
     * @param array{pageIndex?: int, pageCount?: int} $opts
     */
    public function listRecipientTaxProfiles(
        array $opts = [],
        ?RequestOptions $options = null,
    ): TaxCodePaginatedResponse {
        return $this->listEndpoint('/tax-codes/recipient-tax-profile', $opts, $options);
    }

    /**
     * @param array{pageIndex?: int, pageCount?: int} $opts
     */
    private function listEndpoint(string $path, array $opts, ?RequestOptions $options): TaxCodePaginatedResponse
    {
        $query = [];
        if (isset($opts['pageIndex'])) {
            $query['pageIndex'] = $opts['pageIndex'];
        }
        if (isset($opts['pageCount'])) {
            $query['pageCount'] = $opts['pageCount'];
        }
        $response = $this->httpGet($path, $query, $options);
        $payload = $this->decodeBody($response->body);

        $items = isset($payload['items']) && is_array($payload['items']) ? array_values($payload['items']) : [];
        /** @var list<array<string, mixed>> $items */
        return new TaxCodePaginatedResponse(
            items: $items,
            currentPage: isset($payload['currentPage']) && is_int($payload['currentPage']) ? $payload['currentPage'] : null,
            totalPages: isset($payload['totalPages']) && is_int($payload['totalPages']) ? $payload['totalPages'] : null,
            totalCount: isset($payload['totalCount']) && is_int($payload['totalCount']) ? $payload['totalCount'] : null,
        );
    }
}
