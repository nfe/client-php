<?php

declare(strict_types=1);

namespace Nfe\Resource;

/**
 * Natural persons (pessoas físicas / PF).
 *
 * **Stub** — public methods are implemented in `add-entity-resources` (c06).
 */
final class NaturalPeopleResource extends AbstractResource
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
