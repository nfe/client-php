<?php

declare(strict_types=1);

namespace Nfe\Resource;

/**
 * CPF (natural person) lookup. Hosted on a dedicated subdomain.
 *
 * **Stub** — public methods are implemented in `add-lookup-resources` (c07).
 */
final class NaturalPersonLookupResource extends AbstractResource
{
    protected function apiFamily(): string
    {
        return 'natural-person';
    }

    protected function apiVersion(): string
    {
        return 'v3';
    }
}
