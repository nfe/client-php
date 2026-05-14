<?php

declare(strict_types=1);

namespace Nfe\Exception;

/**
 * Raised when the API responds with HTTP 404.
 *
 * Indicates the resource (company, invoice, webhook, ...) does not exist or
 * is not visible to the authenticated account.
 */
final class NotFoundException extends ApiErrorException {}
