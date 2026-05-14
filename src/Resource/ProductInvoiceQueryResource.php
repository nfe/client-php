<?php

declare(strict_types=1);

namespace Nfe\Resource;

/**
 * Read-only queries against the NF-e index.
 *
 * **Stub** — public methods are implemented in `add-lookup-resources` (c07).
 */
final class ProductInvoiceQueryResource extends AbstractResource
{
    protected function apiFamily(): string
    {
        return 'core';
    }

    protected function apiVersion(): string
    {
        return 'v3';
    }
}
