<?php

declare(strict_types=1);

namespace Nfe\Resource;

/**
 * Product invoices (NF-e).
 *
 * **Stub** — public CRUD methods are implemented in `add-invoice-resources` (c05).
 */
final class ProductInvoicesResource extends AbstractResource
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
