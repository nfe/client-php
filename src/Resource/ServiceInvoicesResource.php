<?php

declare(strict_types=1);

namespace Nfe\Resource;

/**
 * Service invoices (NFS-e).
 *
 * **Stub** — public CRUD methods are implemented in the OpenSpec change
 * `add-invoice-resources` (c05). The wiring (apiFamily + apiVersion +
 * Client binding) lives here so the Client surface is stable from v3 day 1.
 */
final class ServiceInvoicesResource extends AbstractResource
{
    protected function apiFamily(): string
    {
        return 'core';
    }

    protected function apiVersion(): string
    {
        return 'v1';
    }
}
