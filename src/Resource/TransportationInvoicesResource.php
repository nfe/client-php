<?php

declare(strict_types=1);

namespace Nfe\Resource;

/**
 * Transportation invoices (CT-e). Hosted at api.nfse.io.
 *
 * **Stub** — public CRUD methods are implemented in `add-invoice-resources` (c05).
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
}
