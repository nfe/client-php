<?php

declare(strict_types=1);

namespace Nfe\Exception;

/**
 * Raised when the API rejects credentials (HTTP 401).
 *
 * Typical causes: missing or invalid API key, key revoked, or key scoped to
 * the wrong environment (e.g., sandbox key used against production).
 */
final class AuthenticationException extends ApiErrorException {}
