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
        $response = $this->httpPost("/companies/{$companyId}/inbound/productinvoices", $data, $options);

        return $this->hydrate(InboundSettings::class, $this->decodeBody($response->body));
    }

    public function disableAutoFetch(string $companyId, ?RequestOptions $options = null): InboundSettings
    {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->httpDelete("/companies/{$companyId}/inbound/productinvoices", $options);

        return $this->hydrate(InboundSettings::class, $this->decodeBody($response->body));
    }

    public function getSettings(string $companyId, ?RequestOptions $options = null): InboundSettings
    {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->httpGet("/companies/{$companyId}/inbound/productinvoices", options: $options);

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
            "/companies/{$companyId}/inbound/{$accessKey}",
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
            "/companies/{$companyId}/inbound/productinvoices/{$accessKey}",
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
            "/companies/{$companyId}/inbound/{$accessKey}/events/{$eventKey}",
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
            "/companies/{$companyId}/inbound/productinvoices/{$accessKey}/events/{$eventKey}",
            options: $options,
        );

        return $this->decodeBody($response->body);
    }

    public function getXml(string $companyId, string $accessKey, ?RequestOptions $options = null): string
    {
        $companyId = IdValidator::companyId($companyId);
        $accessKey = IdValidator::accessKey($accessKey);

        return $this->download(
            "/companies/{$companyId}/inbound/{$accessKey}/xml",
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
            "/companies/{$companyId}/inbound/{$accessKey}/events/{$eventKey}/xml",
            options: $options,
        );
    }

    public function getPdf(string $companyId, string $accessKey, ?RequestOptions $options = null): string
    {
        $companyId = IdValidator::companyId($companyId);
        $accessKey = IdValidator::accessKey($accessKey);

        return $this->download(
            "/companies/{$companyId}/inbound/{$accessKey}/pdf",
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
            "/companies/{$companyId}/inbound/productinvoices/{$accessKey}/json",
            options: $options,
        );

        return $this->decodeBody($response->body);
    }

    /**
     * Manifestar o destinatário (ciência, confirmação, desconhecimento, operação não realizada).
     *
     * A API espera o código numérico SEFAZ no query param `tpEvent` (sondado
     * 2026-07-30: literais são rejeitados pelo binder). Aceita o código direto
     * (`'210210'`) ou os literais legados deste SDK, mapeados assim:
     * `Confirmation`→210200, `Acknowledgement`→210210, `Unknown`→210220,
     * `Refused`→210240.
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
        $tpEvent = self::MANIFEST_EVENT_CODES[trim($manifestType)] ?? trim($manifestType);
        if (preg_match('/^\d{6}$/', $tpEvent) !== 1) {
            throw new \Nfe\Exception\InvalidRequestException(
                'manifestType inválido: use o código SEFAZ de 6 dígitos (210200/210210/210220/210240) ou Confirmation/Acknowledgement/Unknown/Refused.',
            );
        }
        $response = $this->httpPost(
            "/companies/{$companyId}/inbound/{$accessKey}/manifest?" . http_build_query(['tpEvent' => $tpEvent]),
            $data,
            $options,
        );

        return $this->decodeBody($response->body);
    }

    /**
     * Reenvia o webhook para uma NF-e recebida, identificada pela chave de
     * acesso (44 dígitos) OU pelo NSU (1–15 dígitos).
     *
     * @return array<string, mixed>
     */
    public function reprocessWebhook(
        string $companyId,
        string $accessKey,
        ?RequestOptions $options = null,
    ): array {
        $companyId = IdValidator::companyId($companyId);
        $accessKey = IdValidator::accessKeyOrNsu($accessKey);
        $response = $this->httpPost(
            "/companies/{$companyId}/inbound/productinvoices/{$accessKey}/processwebhook",
            options: $options,
        );

        return $this->decodeBody($response->body);
    }

    /** Literais legados do SDK → código numérico SEFAZ do evento de manifestação. */
    private const MANIFEST_EVENT_CODES = [
        'Confirmation'    => '210200',
        'Acknowledgement' => '210210',
        'Unknown'         => '210220',
        'Refused'         => '210240',
    ];
}
