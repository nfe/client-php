<?php

declare(strict_types=1);

namespace Nfe\Resource;

/**
 * Tax calculation (RTC).
 *
 * **Stub** — public methods are implemented in `add-lookup-resources` (c07).
 */
final class TaxCalculationResource extends AbstractResource
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
