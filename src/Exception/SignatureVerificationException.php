<?php

declare(strict_types=1);

namespace Nfe\Exception;

/**
 * Raised when {@see \Nfe\Webhook::constructEvent()} fails to verify the
 * HMAC signature on an incoming webhook payload.
 *
 * Catch this exception and respond with HTTP 403 (Forbidden) to reject the
 * request. Never proceed to process the payload after this exception.
 */
final class SignatureVerificationException extends ApiErrorException {}
