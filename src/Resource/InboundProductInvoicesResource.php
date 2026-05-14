<?php

declare(strict_types=1);

namespace Nfe\Resource;

use Nfe\Http\RequestOptions;
use Nfe\Resource\Dto\InboundProductInvoices\InboundSettings;
use Nfe\Util\IdValidator;

/**
 * Inbound product invoices (NF-e recebidas de fornecedores) — auto-fetch settings,
 * details, events, manifestation, and re-processing of webhooks.
 *
 * Hosted at `https://api.nfse.io` under v2.
 *
 * Paridade com `client-nodejs/src/core/resources/inbound-product-invoices.ts`.
 */
final class InboundProductInvoicesResource extends AbstractResource
{
    protected function apiFamily(): string
    {
        return 'inbound-product';
    }

    protected function apiVersion(): string
    {
        return 'v2';
    }

    /**
     * @param array<string, mixed> $data
     */
    public function enableAutoFetch(
        string $companyId,
        array $data,
        ?RequestOptions $options = null,
    ): InboundSettings {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->httpPut("/companies/{$companyId}/productinvoices/inbound", $data, $options);

        return $this->hydrate(InboundSettings::class, $this->decodeBody($response->body));
    }

    public function disableAutoFetch(string $companyId, ?RequestOptions $options = null): InboundSettings
    {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->httpDelete("/companies/{$companyId}/productinvoices/inbound", $options);

        return $this->hydrate(InboundSettings::class, $this->decodeBody($response->body));
    }

    public function getSettings(string $companyId, ?RequestOptions $options = null): InboundSettings
    {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->httpGet("/companies/{$companyId}/productinvoices/inbound", options: $options);

        return $this->hydrate(InboundSettings::class, $this->decodeBody($response->body));
    }

    /**
     * @return array<string, mixed>
     */
    public function getDetails(string $companyId, string $accessKey, ?RequestOptions $options = null): array
    {
        $companyId = IdValidator::companyId($companyId);
        $accessKey = IdValidator::accessKey($accessKey);
        $response = $this->httpGet(
            "/companies/{$companyId}/productinvoices/received/{$accessKey}",
            options: $options,
        );

        return $this->decodeBody($response->body);
    }

    /**
     * @return array<string, mixed>
     */
    public function getProductInvoiceDetails(
        string $companyId,
        string $accessKey,
        ?RequestOptions $options = null,
    ): array {
        $companyId = IdValidator::companyId($companyId);
        $accessKey = IdValidator::accessKey($accessKey);
        $response = $this->httpGet(
            "/companies/{$companyId}/productinvoices/received/{$accessKey}/productinvoice",
            options: $options,
        );

        return $this->decodeBody($response->body);
    }

    /**
     * @return array<string, mixed>
     */
    public function getEventDetails(
        string $companyId,
        string $accessKey,
        string $eventKey,
        ?RequestOptions $options = null,
    ): array {
        $companyId = IdValidator::companyId($companyId);
        $accessKey = IdValidator::accessKey($accessKey);
        $eventKey = IdValidator::eventKey($eventKey);
        $response = $this->httpGet(
            "/companies/{$companyId}/productinvoices/received/{$accessKey}/events/{$eventKey}",
            options: $options,
        );

        return $this->decodeBody($response->body);
    }

    /**
     * @return array<string, mixed>
     */
    public function getProductInvoiceEventDetails(
        string $companyId,
        string $accessKey,
        string $eventKey,
        ?RequestOptions $options = null,
    ): array {
        $companyId = IdValidator::companyId($companyId);
        $accessKey = IdValidator::accessKey($accessKey);
        $eventKey = IdValidator::eventKey($eventKey);
        $response = $this->httpGet(
            "/companies/{$companyId}/productinvoices/received/{$accessKey}/events/{$eventKey}/productinvoice",
            options: $options,
        );

        return $this->decodeBody($response->body);
    }

    public function getXml(string $companyId, string $accessKey, ?RequestOptions $options = null): string
    {
        $companyId = IdValidator::companyId($companyId);
        $accessKey = IdValidator::accessKey($accessKey);

        return $this->download(
            "/companies/{$companyId}/productinvoices/received/{$accessKey}/xml",
            options: $options,
        );
    }

    public function getEventXml(
        string $companyId,
        string $accessKey,
        string $eventKey,
        ?RequestOptions $options = null,
    ): string {
        $companyId = IdValidator::companyId($companyId);
        $accessKey = IdValidator::accessKey($accessKey);
        $eventKey = IdValidator::eventKey($eventKey);

        return $this->download(
            "/companies/{$companyId}/productinvoices/received/{$accessKey}/events/{$eventKey}/xml",
            options: $options,
        );
    }

    public function getPdf(string $companyId, string $accessKey, ?RequestOptions $options = null): string
    {
        $companyId = IdValidator::companyId($companyId);
        $accessKey = IdValidator::accessKey($accessKey);

        return $this->download(
            "/companies/{$companyId}/productinvoices/received/{$accessKey}/pdf",
            options: $options,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getJson(string $companyId, string $accessKey, ?RequestOptions $options = null): array
    {
        $companyId = IdValidator::companyId($companyId);
        $accessKey = IdValidator::accessKey($accessKey);
        $response = $this->httpGet(
            "/companies/{$companyId}/productinvoices/received/{$accessKey}/json",
            options: $options,
        );

        return $this->decodeBody($response->body);
    }

    /**
     * Manifestar o destinatário (ciência, confirmação, desconhecimento, refutação).
     *
     * @param array<string, mixed> $data Pode conter justification, etc. dependendo do tipo.
     * @return array<string, mixed>
     */
    public function manifest(
        string $companyId,
        string $accessKey,
        string $manifestType,
        array $data = [],
        ?RequestOptions $options = null,
    ): array {
        $companyId = IdValidator::companyId($companyId);
        $accessKey = IdValidator::accessKey($accessKey);
        if (trim($manifestType) === '') {
            throw new \Nfe\Exception\InvalidRequestException('manifestType é obrigatório (Confirmation/Acknowledgement/Unknown/Refused).');
        }
        $response = $this->httpPut(
            "/companies/{$companyId}/productinvoices/received/{$accessKey}/manifest/{$manifestType}",
            $data,
            $options,
        );

        return $this->decodeBody($response->body);
    }

    /**
     * Reenvia o webhook para uma NF-e recebida.
     *
     * @return array<string, mixed>
     */
    public function reprocessWebhook(
        string $companyId,
        string $accessKey,
        ?RequestOptions $options = null,
    ): array {
        $companyId = IdValidator::companyId($companyId);
        $accessKey = IdValidator::accessKey($accessKey);
        $response = $this->httpPost(
            "/companies/{$companyId}/productinvoices/received/{$accessKey}/webhook/reprocess",
            options: $options,
        );

        return $this->decodeBody($response->body);
    }
}
