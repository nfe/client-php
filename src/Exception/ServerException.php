<?php

declare(strict_types=1);

namespace Nfe\Exception;

/**
 * Raised on HTTP 5xx responses that survived the retry policy.
 *
 * Indicates a transient or persistent failure on NFE.io's side. Inspect
 * {@see self::$responseBody} for the API error envelope and consider
 * raising with the NFE.io support team if the failure persists.
 */
final class ServerException extends ApiErrorException {}
