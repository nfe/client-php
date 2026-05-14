<?php

declare(strict_types=1);

namespace Nfe\Resource;

use Nfe\Http\RequestOptions;
use Nfe\Resource\Dto\ProductInvoiceQuery\ProductInvoiceDetails;
use Nfe\Util\IdValidator;

/**
 * NF-e read-only query by access key.
 *
 * Hosted at `https://nfe.api.nfe.io` under v2 (path `/v2/productinvoices/...`).
 */
final class ProductInvoiceQueryResource extends AbstractResource
{
    protected function apiFamily(): string
    {
        return 'nfe-query';
    }

    protected function apiVersion(): string
    {
        return 'v2';
    }

    public function retrieve(string $accessKey, ?RequestOptions $options = null): ProductInvoiceDetails
    {
        $accessKey = IdValidator::accessKey($accessKey);
        $response = $this->httpGet("/productinvoices/{$accessKey}", options: $options);
        $payload = $this->decodeBody($response->body);

        return new ProductInvoiceDetails(
            accessKey: isset($payload['accessKey']) && is_string($payload['accessKey']) ? $payload['accessKey'] : null,
            number: isset($payload['number']) && is_int($payload['number']) ? $payload['number'] : null,
            serie: isset($payload['serie']) && is_int($payload['serie']) ? $payload['serie'] : null,
            status: isset($payload['status']) && is_string($payload['status']) ? $payload['status'] : null,
            issuedOn: isset($payload['issuedOn']) && is_string($payload['issuedOn']) ? $payload['issuedOn'] : null,
            raw: $payload,
        );
    }

    public function downloadPdf(string $accessKey, ?RequestOptions $options = null): string
    {
        $accessKey = IdValidator::accessKey($accessKey);

        return $this->download("/productinvoices/{$accessKey}.pdf", options: $options);
    }

    public function downloadXml(string $accessKey, ?RequestOptions $options = null): string
    {
        $accessKey = IdValidator::accessKey($accessKey);

        return $this->download("/productinvoices/{$accessKey}.xml", options: $options);
    }

    /**
     * @return array<string, mixed>
     */
    public function listEvents(string $accessKey, ?RequestOptions $options = null): array
    {
        $accessKey = IdValidator::accessKey($accessKey);
        $response = $this->httpGet("/productinvoices/events/{$accessKey}", options: $options);

        return $this->decodeBody($response->body);
    }
}
