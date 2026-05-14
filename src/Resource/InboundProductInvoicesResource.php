<?php

declare(strict_types=1);

namespace Nfe\Resource;

/**
 * Inbound product invoices (recebimento de NF-e).
 *
 * **Stub** — public methods are implemented in `add-invoice-resources` (c05).
 */
final class InboundProductInvoicesResource extends AbstractResource
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
