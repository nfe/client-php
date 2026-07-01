<?php

declare(strict_types=1);

namespace Nfe\Resource;

use Nfe\Http\RequestOptions;
use Nfe\Resource\Dto\Webhooks\Webhook as WebhookDto;
use Nfe\Util\IdValidator;
use Nfe\Util\ListResponse;

/**
 * Webhook subscription management (CRUD + test delivery).
 *
 * Not to be confused with {@see \Nfe\Webhook} — the static helper that
 * verifies HMAC signatures on inbound webhook payloads. This resource is the
 * server-side management API: registering URLs, listing subscriptions, etc.
 *
 * Routed via `main` family → `https://api.nfe.io/v1` (confirmed against
 * Node SDK: `webhooks` resource uses `getMainHttpClient()` which defaults
 * to `api.nfe.io/v1`).
 */
final class WebhooksResource extends AbstractResource
{
    protected function apiFamily(): string
    {
        return 'main';
    }

    protected function apiVersion(): string
    {
        return 'v1';
    }

    /**
     * @return ListResponse<WebhookDto>
     */
    public function list(string $companyId, ?RequestOptions $options = null): ListResponse
    {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->httpGet("/companies/{$companyId}/webhooks", options: $options);
        $payload = $this->decodeBody($response->body);

        return $this->hydrateList(WebhookDto::class, $payload, 'webhooks');
    }

    /**
     * @param array<string, mixed> $data Aceita `url` (required), `events` (list<string>),
     *                                    `secret` (string, optional).
     */
    public function create(string $companyId, array $data, ?RequestOptions $options = null): WebhookDto
    {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->httpPost("/companies/{$companyId}/webhooks", $data, $options);

        return $this->hydrate(WebhookDto::class, $this->decodeBody($response->body));
    }

    public function retrieve(string $companyId, string $webhookId, ?RequestOptions $options = null): WebhookDto
    {
        $companyId = IdValidator::companyId($companyId);
        $webhookId = IdValidator::invoiceId($webhookId);
        $response = $this->httpGet("/companies/{$companyId}/webhooks/{$webhookId}", options: $options);

        return $this->hydrate(WebhookDto::class, $this->decodeBody($response->body));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(
        string $companyId,
        string $webhookId,
        array $data,
        ?RequestOptions $options = null,
    ): WebhookDto {
        $companyId = IdValidator::companyId($companyId);
        $webhookId = IdValidator::invoiceId($webhookId);
        $response = $this->httpPut("/companies/{$companyId}/webhooks/{$webhookId}", $data, $options);

        return $this->hydrate(WebhookDto::class, $this->decodeBody($response->body));
    }

    public function delete(string $companyId, string $webhookId, ?RequestOptions $options = null): void
    {
        $companyId = IdValidator::companyId($companyId);
        $webhookId = IdValidator::invoiceId($webhookId);
        parent::httpDelete("/companies/{$companyId}/webhooks/{$webhookId}", $options);
    }

    /**
     * Triggers a test delivery to the webhook URL.
     *
     * @return array<string, mixed>
     */
    public function test(string $companyId, string $webhookId, ?RequestOptions $options = null): array
    {
        $companyId = IdValidator::companyId($companyId);
        $webhookId = IdValidator::invoiceId($webhookId);
        $response = $this->httpPost("/companies/{$companyId}/webhooks/{$webhookId}/test", [], $options);

        return $this->decodeBody($response->body);
    }

    /**
     * Static list of available webhook event types (mirrors Node SDK hard-coded list).
     *
     * @return list<string>
     */
    public function getAvailableEvents(): array
    {
        return [
            'invoice.issued',
            'invoice.cancelled',
            'invoice.failed',
            'invoice.processing',
            'company.created',
            'company.updated',
            'company.deleted',
        ];
    }
}
