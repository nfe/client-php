<?php

declare(strict_types=1);

namespace Nfe\Resource;

/**
 * CNPJ (legal entity) lookup. Hosted on a dedicated subdomain.
 *
 * **Stub** — public methods are implemented in `add-lookup-resources` (c07).
 */
final class LegalEntityLookupResource extends AbstractResource
{
    protected function apiFamily(): string
    {
        return 'legal-entity';
    }

    protected function apiVersion(): string
    {
        return 'v3';
    }
}
