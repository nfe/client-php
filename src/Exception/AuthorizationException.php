<?php

declare(strict_types=1);

namespace Nfe\Exception;

/**
 * Raised when the API accepts the credentials but refuses the operation (HTTP 403).
 *
 * Distinct from {@see AuthenticationException} (401, "who are you?"): the API
 * recognises the caller but the action is not permitted. Typical causes:
 *
 * - The API key's plan does not include the targeted data service (e.g., CNPJ
 *   or CEP lookups often require a paid data-services plan)
 * - The key is scoped to a different company / environment than the request
 * - The company exists but is not yet enabled for the targeted invoice type
 *   (e.g., listing service invoices on a company without NFS-e municipal
 *   configuration)
 *
 * Inspect `$responseBody` and the API's RFC 9110 problem-details payload for
 * the specific reason.
 */
final class AuthorizationException extends ApiErrorException {}
