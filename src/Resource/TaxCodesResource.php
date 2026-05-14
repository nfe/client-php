<?php

declare(strict_types=1);

namespace Nfe\Resource;

/**
 * Tax code tables (NBS, CNAE).
 *
 * **Stub** — public methods are implemented in `add-lookup-resources` (c07).
 */
final class TaxCodesResource extends AbstractResource
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
