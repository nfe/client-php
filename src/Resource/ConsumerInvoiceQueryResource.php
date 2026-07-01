<?php

declare(strict_types=1);

namespace Nfe\Resource;

use Nfe\Http\RequestOptions;
use Nfe\Resource\Dto\ConsumerInvoiceQuery\TaxCoupon;
use Nfe\Util\IdValidator;

/**
 * NFC-e read-only query by access key.
 *
 * Hosted at `https://nfe.api.nfe.io` (same host as `ProductInvoiceQueryResource`)
 * under v1 (path `/v1/consumerinvoices/coupon/...`).
 */
final class ConsumerInvoiceQueryResource extends AbstractResource
{
    protected function apiFamily(): string
    {
        return 'nfe-query';
    }

    protected function apiVersion(): string
    {
        return 'v1';
    }

    public function retrieve(string $accessKey, ?RequestOptions $options = null): TaxCoupon
    {
        $accessKey = IdValidator::accessKey($accessKey);
        $response = $this->httpGet("/consumerinvoices/coupon/{$accessKey}", options: $options);
        $payload = $this->decodeBody($response->body);

        return $this->hydrate(TaxCoupon::class, $payload);
    }

    public function downloadXml(string $accessKey, ?RequestOptions $options = null): string
    {
        $accessKey = IdValidator::accessKey($accessKey);

        return $this->download("/consumerinvoices/coupon/{$accessKey}.xml", options: $options);
    }
}
