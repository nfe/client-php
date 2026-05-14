<?php

declare(strict_types=1);

namespace Nfe\Resource;

/**
 * CEP (postal code) lookup.
 *
 * **Stub** — public methods are implemented in `add-lookup-resources` (c07).
 */
final class AddressesResource extends AbstractResource
{
    protected function apiFamily(): string
    {
        return 'addresses';
    }

    protected function apiVersion(): string
    {
        return 'v3';
    }
}
