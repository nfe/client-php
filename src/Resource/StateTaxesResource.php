<?php

declare(strict_types=1);

namespace Nfe\Resource;

/**
 * State tax registrations (contribuintes, IE).
 *
 * **Stub** — public methods are implemented in `add-lookup-resources` (c07).
 */
final class StateTaxesResource extends AbstractResource
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
