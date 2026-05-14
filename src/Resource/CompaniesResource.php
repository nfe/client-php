<?php

declare(strict_types=1);

namespace Nfe\Resource;

/**
 * Companies (issuers of invoices).
 *
 * **Stub** — public methods are implemented in `add-entity-resources` (c06).
 */
final class CompaniesResource extends AbstractResource
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
