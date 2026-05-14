<?php

declare(strict_types=1);

namespace Nfe\Resource;

/**
 * Consumer invoices (NFC-e).
 *
 * **Stub** — public CRUD methods are implemented in `add-invoice-resources` (c05).
 */
final class ConsumerInvoicesResource extends AbstractResource
{
    protected function apiFamily(): string
    {
        return 'core';
    }

    protected function apiVersion(): string
    {
        return 'v2';
    }
}
