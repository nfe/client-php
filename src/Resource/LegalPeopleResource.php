<?php

declare(strict_types=1);

namespace Nfe\Resource;

/**
 * Legal persons (pessoas jurídicas / PJ).
 *
 * **Stub** — public methods are implemented in `add-entity-resources` (c06).
 */
final class LegalPeopleResource extends AbstractResource
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
