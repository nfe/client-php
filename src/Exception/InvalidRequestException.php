<?php

declare(strict_types=1);

namespace Nfe\Exception;

/**
 * Raised for malformed or semantically invalid requests (HTTP 400).
 *
 * The {@see self::$responseBody} usually contains a structured error payload
 * describing which fields failed validation.
 */
final class InvalidRequestException extends ApiErrorException {}
