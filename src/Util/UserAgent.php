<?php

declare(strict_types=1);

namespace Nfe\Util;

use Nfe\Version;

/**
 * Builds the SDK's User-Agent header. Format:
 *
 *     Nfe-PHP/<sdk-version> php/<php-version> curl/<curl-version> [suffix]
 *
 * The suffix is optional and set via Config to let integrators identify
 * themselves (e.g., "WHMCS/8.10 nfeio-module/3.2.0").
 */
final class UserAgent
{
    public static function build(?string $suffix = null): string
    {
        $parts = [
            'Nfe-PHP/' . Version::CURRENT,
            'php/' . PHP_VERSION,
        ];

        if (function_exists('curl_version')) {
            $info = curl_version();
            if (is_array($info) && isset($info['version']) && is_string($info['version'])) {
                $parts[] = 'curl/' . $info['version'];
            }
        }

        $ua = implode(' ', $parts);

        if ($suffix !== null && $suffix !== '') {
            $ua .= ' ' . $suffix;
        }

        return $ua;
    }
}
