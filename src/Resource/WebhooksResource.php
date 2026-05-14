<?php

declare(strict_types=1);

namespace Nfe\Resource;

/**
 * Webhook configuration (create, update, delete, ping, list event types).
 *
 * **Stub** — public methods are implemented in `add-entity-resources` (c06).
 *
 * Signature verification for inbound webhooks lives in {@see \Nfe\Webhook}
 * (also added in c06): HMAC-SHA1 over `X-Hub-Signature`, confirmed canonical
 * scheme with the NFE.io API team on 2026-05-13.
 */
final class WebhooksResource extends AbstractResource
{
    protected function apiFamily(): string
    {
        return 'webhooks';
    }

    protected function apiVersion(): string
    {
        return 'v2';
    }
}
