<?php

declare(strict_types=1);

namespace Nfe;

/**
 * Programmatic access to the SDK's semantic version.
 *
 * Update {@see self::CURRENT} as part of the release process. See
 * `scripts/release.sh` (added in change add-release-tooling).
 */
final class Version
{
    public const string CURRENT = '3.0.0-dev';
}
