<?php

declare(strict_types=1);

namespace Nfe;

/**
 * Target environment for the NFE.io API.
 *
 * Selection drives base URL routing in {@see Config::baseUrlForApi()}.
 * Sandbox traffic is rate-limited and isolated from production data.
 */
enum Environment: string
{
    case Production = 'production';
    case Sandbox = 'sandbox';
}
