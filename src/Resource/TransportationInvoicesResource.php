<?php

declare(strict_types=1);

namespace Nfe\Resource;

use Nfe\Http\RequestOptions;
use Nfe\Resource\Dto\TransportationInvoices\InboundSettings;
use Nfe\Util\IdValidator;

/**
 * Transportation invoices (CT-e) — auto-fetch settings + queries by access key.
 *
 * Hosted at `https://api.nfse.io` under v2. Despite the name "Transportation
 * Invoices", this resource doesn't emit CT-e (NFE.io só consulta CT-e
 * recebidas); it manages the auto-fetch configuration and exposes lookup
 * by 44-digit access key plus event retrieval.
 *
 * Paridade com `client-nodejs/src/core/resources/transportation-invoices.ts`.
 */
final class TransportationInvoicesResource extends AbstractResource
{
    protected function apiFamily(): string
    {
        return 'cte';
    }

    protected function apiVersion(): string
    {
        return 'v2';
    }

    /**
     * Habilita auto-fetch de CT-e para a empresa.
     *
     * @param array<string, mixed> $data
     */
    public function enable(
        string $companyId,
        array $data,
        ?RequestOptions $options = null,
    ): InboundSettings {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->httpPut("/companies/{$companyId}/cte/inbound", $data, $options);
        $payload = $this->decodeBody($response->body);

        return $this->hydrate(InboundSettings::class, $payload);
    }

    public function disable(string $companyId, ?RequestOptions $options = null): InboundSettings
    {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->httpDelete("/companies/{$companyId}/cte/inbound", $options);
        $payload = $this->decodeBody($response->body);

        return $this->hydrate(InboundSettings::class, $payload);
    }

    public function getSettings(string $companyId, ?RequestOptions $options = null): InboundSettings
    {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->httpGet("/companies/{$companyId}/cte/inbound", options: $options);
        $payload = $this->decodeBody($response->body);

        return $this->hydrate(InboundSettings::class, $payload);
    }

    /**
     * Consulta um CT-e específico por access key (44 dígitos).
     *
     * @return array<string, mixed>
     */
    public function retrieve(
        string $companyId,
        string $accessKey,
        ?RequestOptions $options = null,
    ): array {
        $companyId = IdValidator::companyId($companyId);
        $accessKey = IdValidator::accessKey($accessKey);
        $response = $this->httpGet("/companies/{$companyId}/cte/{$accessKey}", options: $options);

        return $this->decodeBody($response->body);
    }

    public function downloadXml(
        string $companyId,
        string $accessKey,
        ?RequestOptions $options = null,
    ): string {
        $companyId = IdValidator::companyId($companyId);
        $accessKey = IdValidator::accessKey($accessKey);

        return $this->download("/companies/{$companyId}/cte/{$accessKey}/xml", options: $options);
    }

    /**
     * @return array<string, mixed>
     */
    public function getEvent(
        string $companyId,
        string $accessKey,
        string $eventKey,
        ?RequestOptions $options = null,
    ): array {
        $companyId = IdValidator::companyId($companyId);
        $accessKey = IdValidator::accessKey($accessKey);
        $eventKey = IdValidator::eventKey($eventKey);
        $response = $this->httpGet(
            "/companies/{$companyId}/cte/{$accessKey}/events/{$eventKey}",
            options: $options,
        );

        return $this->decodeBody($response->body);
    }

    public function downloadEventXml(
        string $companyId,
        string $accessKey,
        string $eventKey,
        ?RequestOptions $options = null,
    ): string {
        $companyId = IdValidator::companyId($companyId);
        $accessKey = IdValidator::accessKey($accessKey);
        $eventKey = IdValidator::eventKey($eventKey);

        return $this->download(
            "/companies/{$companyId}/cte/{$accessKey}/events/{$eventKey}/xml",
            options: $options,
        );
    }
}
