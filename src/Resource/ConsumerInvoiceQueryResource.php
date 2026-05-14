<?php

declare(strict_types=1);

namespace Nfe\Resource;

/**
 * Read-only queries against the NFC-e index.
 *
 * **Stub** — public methods are implemented in `add-lookup-resources` (c07).
 */
final class ConsumerInvoiceQueryResource extends AbstractResource
{
    protected function apiFamily(): string
    {
        return 'consumer-invoice-query';
    }

    protected function apiVersion(): string
    {
        return 'v3';
    }
}
