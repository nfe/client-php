<?php

declare(strict_types=1);

namespace Nfe\Resource;

use Nfe\Http\RequestOptions;
use Nfe\Resource\Dto\Webhooks\AccountWebhook;
use Nfe\Resource\Dto\Webhooks\Webhook as WebhookDto;
use Nfe\Util\IdValidator;
use Nfe\Util\ListResponse;

/**
 * Webhook subscription management.
 *
 * Not to be confused with {@see \Nfe\Webhook} — the static helper that
 * verifies HMAC signatures on inbound webhook payloads. This resource is the
 * server-side management API: registering URLs, listing subscriptions, etc.
 *
 * The live contract is **account-scoped**: `/v2/webhooks` on `api.nfe.io`,
 * with create/update requests wrapped in a `{"webHook": {...}}` envelope and
 * enveloped responses (confirmed by live probes on three accounts,
 * 2026-07-02/03, matching the `/v2/webhooks` paths in
 * `openapi/nf-servico-v1.yaml`). Use the `*AccountWebhook*` methods.
 *
 * The legacy company-scoped methods (`list`, `create`, `retrieve`, `update`,
 * `delete`, `test`) target `/v1/companies/{id}/webhooks`, a route that
 * returns 404 on the current API — they are kept only for BC and are all
 * `@deprecated`.
 *
 * Contract source of truth: the OpenAPI spec plus live probes — never a
 * sibling SDK.
 *
 * Routing note: this resource spans two API versions on the `main` host, so
 * {@see self::apiVersion()} returns `''` and every path carries its version
 * segment explicitly.
 */
final class WebhooksResource extends AbstractResource
{
    protected function apiFamily(): string
    {
        return 'main';
    }

    protected function apiVersion(): string
    {
        // Empty on purpose: company-scoped methods hit /v1 and account-scoped
        // methods hit /v2, so each path is written fully versioned.
        return '';
    }

    // -------------------------------------------------------------------
    // Account-scoped webhooks (/v2/webhooks) — the live API contract
    // -------------------------------------------------------------------

    /**
     * Lists all webhooks registered on the authenticated account.
     *
     * Unwraps the `{"webHooks": [...]}` envelope returned by
     * `GET /v2/webhooks`.
     *
     * @return ListResponse<AccountWebhook>
     */
    public function listAccountWebhooks(?RequestOptions $options = null): ListResponse
    {
        $response = $this->httpGet('/v2/webhooks', options: $options);
        $payload = $this->decodeBody($response->body);

        return $this->hydrateList(AccountWebhook::class, $payload, 'webHooks');
    }

    /**
     * Creates an account webhook via `POST /v2/webhooks`.
     *
     * The request body is wrapped in the mandatory `webHook` envelope (a bare
     * body is rejected with `400 "missing required properties: 'webHook'"`)
     * and the `201 {"webHook": {...}}` response is unwrapped.
     *
     * NFE.io **pings the `uri` at creation time** with an HTTP POST and
     * requires a 2xx response — a non-responsive URL fails the create.
     *
     * The `secret` must be 32–64 characters; it is echoed back on create but
     * omitted on subsequent reads, so store it if you need it for signature
     * verification ({@see \Nfe\Webhook::verifySignature()}).
     *
     * @param array<string, mixed> $data Accepts `uri` (required), `contentType`
     *                                   (e.g. `"json"`), `secret` (32–64 chars),
     *                                   `filters` (list<string>, see
     *                                   {@see self::fetchEventTypes()}),
     *                                   `insecureSsl`, `headers`, `properties`,
     *                                   `status`.
     */
    public function createAccountWebhook(array $data, ?RequestOptions $options = null): AccountWebhook
    {
        $response = $this->httpPost('/v2/webhooks', ['webHook' => $data], $options);
        $inner = $this->unwrap($this->decodeBody($response->body), 'webHook');

        return $this->hydrate(AccountWebhook::class, $inner + ['raw' => $inner]);
    }

    /**
     * Retrieves a single account webhook (`GET /v2/webhooks/{id}`).
     *
     * Unwraps the `{"webHook": {...}}` envelope, falling back to the raw body
     * if the envelope is absent. The `secret` is omitted on reads.
     */
    public function retrieveAccountWebhook(string $webhookId, ?RequestOptions $options = null): AccountWebhook
    {
        $webhookId = IdValidator::invoiceId($webhookId);
        $response = $this->httpGet("/v2/webhooks/{$webhookId}", options: $options);
        $inner = $this->unwrap($this->decodeBody($response->body), 'webHook');

        return $this->hydrate(AccountWebhook::class, $inner + ['raw' => $inner]);
    }

    /**
     * Updates an account webhook via `PUT /v2/webhooks/{id}`.
     *
     * ⚠️ **`PUT` is a full replacement, not a merge**: any field omitted from
     * `$data` resets to its default — an update without `status` deactivates
     * the webhook (live-confirmed). Always start from the current state:
     *
     * ```php
     * $current = $nfe->webhooks->retrieveAccountWebhook($id);
     * $nfe->webhooks->updateAccountWebhook($id, [
     *     'uri'         => $current->uri,
     *     'contentType' => $current->contentType,
     *     'status'      => $current->status,
     *     'filters'     => ['service_invoice.issued_successfully'], // the change
     * ]);
     * ```
     *
     * The request body is wrapped in the mandatory `webHook` envelope and the
     * enveloped response is unwrapped.
     *
     * @param array<string, mixed> $data Full webhook state (see the warning above).
     */
    public function updateAccountWebhook(
        string $webhookId,
        array $data,
        ?RequestOptions $options = null,
    ): AccountWebhook {
        $webhookId = IdValidator::invoiceId($webhookId);
        $response = $this->httpPut("/v2/webhooks/{$webhookId}", ['webHook' => $data], $options);
        $inner = $this->unwrap($this->decodeBody($response->body), 'webHook');

        return $this->hydrate(AccountWebhook::class, $inner + ['raw' => $inner]);
    }

    /**
     * Deletes a single account webhook (`DELETE /v2/webhooks/{id}`).
     */
    public function deleteAccountWebhook(string $webhookId, ?RequestOptions $options = null): void
    {
        $webhookId = IdValidator::invoiceId($webhookId);
        $this->httpDelete("/v2/webhooks/{$webhookId}", $options);
    }

    /**
     * ⚠️ **Destructive**: deletes ALL webhooks on the authenticated account
     * (`DELETE /v2/webhooks`, no id). There is no undo — every subscription is
     * removed in a single call. Deliberately named apart from
     * {@see self::deleteAccountWebhook()} so a missing id can never silently
     * escalate a single delete into a bulk one.
     */
    public function deleteAllAccountWebhooks(?RequestOptions $options = null): void
    {
        $this->httpDelete('/v2/webhooks', $options);
    }

    /**
     * Triggers a test delivery ping to an account webhook
     * (`PUT /v2/webhooks/{id}/pings`, responds 204).
     */
    public function pingAccountWebhook(string $webhookId, ?RequestOptions $options = null): void
    {
        $webhookId = IdValidator::invoiceId($webhookId);
        $this->httpPut("/v2/webhooks/{$webhookId}/pings", null, $options);
    }

    /**
     * Fetches the live list of webhook event-type ids
     * (`GET /v2/webhooks/eventTypes`).
     *
     * Returns ids following the `resource.event_action` pattern — e.g.
     * `service_invoice.issued_successfully`, `product_invoice.*`,
     * `consumer_invoice.*` (46 ids at probe time). Use these as `filters`
     * when creating/updating a webhook.
     *
     * @return list<string>
     */
    public function fetchEventTypes(?RequestOptions $options = null): array
    {
        $response = $this->httpGet('/v2/webhooks/eventTypes', options: $options);
        $payload = $this->decodeBody($response->body);

        $ids = [];
        $eventTypes = $payload['eventTypes'] ?? [];
        if (is_array($eventTypes)) {
            foreach ($eventTypes as $eventType) {
                if (is_array($eventType) && isset($eventType['id']) && is_string($eventType['id'])) {
                    $ids[] = $eventType['id'];
                }
            }
        }

        return $ids;
    }

    // -------------------------------------------------------------------
    // Legacy company-scoped webhooks (/v1/companies/{id}/webhooks)
    // The route returns 404 on the current API (confirmed on three
    // accounts, 2026-07-02/03). Kept for BC only; removal next major.
    // -------------------------------------------------------------------

    /**
     * @deprecated `/v1/companies/{id}/webhooks` returns 404 on the current API
     *             (confirmed on three accounts, 2026-07-02/03). Use
     *             {@see self::listAccountWebhooks()}.
     *
     * @return ListResponse<WebhookDto>
     */
    public function list(string $companyId, ?RequestOptions $options = null): ListResponse
    {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->httpGet("/v1/companies/{$companyId}/webhooks", options: $options);
        $payload = $this->decodeBody($response->body);

        return $this->hydrateList(WebhookDto::class, $payload, 'webhooks');
    }

    /**
     * @deprecated `/v1/companies/{id}/webhooks` returns 404 on the current API
     *             (confirmed on three accounts, 2026-07-02/03). Use
     *             {@see self::createAccountWebhook()}.
     *
     * @param array<string, mixed> $data Aceita `url` (required), `events` (list<string>),
     *                                    `secret` (string, optional).
     */
    public function create(string $companyId, array $data, ?RequestOptions $options = null): WebhookDto
    {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->httpPost("/v1/companies/{$companyId}/webhooks", $data, $options);

        return $this->hydrate(WebhookDto::class, $this->decodeBody($response->body));
    }

    /**
     * @deprecated `/v1/companies/{id}/webhooks` returns 404 on the current API
     *             (confirmed on three accounts, 2026-07-02/03). Use
     *             {@see self::retrieveAccountWebhook()}.
     */
    public function retrieve(string $companyId, string $webhookId, ?RequestOptions $options = null): WebhookDto
    {
        $companyId = IdValidator::companyId($companyId);
        $webhookId = IdValidator::invoiceId($webhookId);
        $response = $this->httpGet("/v1/companies/{$companyId}/webhooks/{$webhookId}", options: $options);

        return $this->hydrate(WebhookDto::class, $this->decodeBody($response->body));
    }

    /**
     * @deprecated `/v1/companies/{id}/webhooks` returns 404 on the current API
     *             (confirmed on three accounts, 2026-07-02/03). Use
     *             {@see self::updateAccountWebhook()}.
     *
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
        $response = $this->httpPut("/v1/companies/{$companyId}/webhooks/{$webhookId}", $data, $options);

        return $this->hydrate(WebhookDto::class, $this->decodeBody($response->body));
    }

    /**
     * @deprecated `/v1/companies/{id}/webhooks` returns 404 on the current API
     *             (confirmed on three accounts, 2026-07-02/03). Use
     *             {@see self::deleteAccountWebhook()}.
     */
    public function delete(string $companyId, string $webhookId, ?RequestOptions $options = null): void
    {
        $companyId = IdValidator::companyId($companyId);
        $webhookId = IdValidator::invoiceId($webhookId);
        parent::httpDelete("/v1/companies/{$companyId}/webhooks/{$webhookId}", $options);
    }

    /**
     * Triggers a test delivery to the webhook URL.
     *
     * @deprecated `/v1/companies/{id}/webhooks` returns 404 on the current API
     *             (confirmed on three accounts, 2026-07-02/03). Use
     *             {@see self::pingAccountWebhook()}.
     *
     * @return array<string, mixed>
     */
    public function test(string $companyId, string $webhookId, ?RequestOptions $options = null): array
    {
        $companyId = IdValidator::companyId($companyId);
        $webhookId = IdValidator::invoiceId($webhookId);
        $response = $this->httpPost("/v1/companies/{$companyId}/webhooks/{$webhookId}/test", [], $options);

        return $this->decodeBody($response->body);
    }

    /**
     * Static list of legacy webhook event types.
     *
     * @deprecated These `invoice.*`/`company.*` literals do not exist on the
     *             live API — the real ids follow `service_invoice.*` /
     *             `product_invoice.*` / `consumer_invoice.*`. Use
     *             {@see self::fetchEventTypes()} for the live list.
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
