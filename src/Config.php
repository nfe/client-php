<?php

declare(strict_types=1);

namespace Nfe;

use Nfe\Exception\InvalidRequestException;
use Nfe\Http\RetryPolicy;
use Nfe\Http\Transport;
use Psr\Log\LoggerInterface;

/**
 * Immutable configuration consumed by {@see Client}.
 *
 * Build directly or accept the convenience constructor on Client which
 * builds one for you from primitives.
 */
final readonly class Config
{
    /**
     * @param string                $apiKey          NFE.io API key. Required.
     * @param Environment           $environment     Production or Sandbox routing.
     * @param int                   $timeout         Default per-request timeout in seconds.
     * @param RetryPolicy           $retry           Retry behavior for transient failures.
     * @param Transport|null        $transport       Override the default cURL transport. Null = use default.
     * @param LoggerInterface|null  $logger          Optional PSR-3 logger. PSR packages are NOT required at runtime.
     * @param string|null           $userAgentSuffix Optional integrator identifier appended to the SDK's User-Agent.
     */
    public function __construct(
        public string $apiKey,
        public Environment $environment = Environment::Production,
        public int $timeout = 60,
        public RetryPolicy $retry = new RetryPolicy(),
        public ?Transport $transport = null,
        public ?LoggerInterface $logger = null,
        public ?string $userAgentSuffix = null,
    ) {
        if (trim($apiKey) === '') {
            throw new InvalidRequestException('Nfe\\Config: apiKey must be a non-empty string.');
        }
        if ($timeout <= 0) {
            throw new InvalidRequestException('Nfe\\Config: timeout must be positive.');
        }
    }

    /**
     * Resolve the base URL for a given NFE.io product family.
     *
     * Per-family hosts mirror the Node.js SDK so resource code in c05-c07 can
     * declare its target host as a stable identifier.
     */
    public function baseUrlForApi(string $api): string
    {
        $isProd = $this->environment === Environment::Production;

        return match ($api) {
            // Main API host (most endpoints, including invoice resources, companies, webhooks, addresses).
            'core', 'invoices', 'companies', 'addresses', 'webhooks' => 'https://api.nfe.io',

            // CT-e (transportation invoices) and consumer invoice query live under api.nfse.io.
            'cte', 'transportation', 'consumer-invoice-query' => 'https://api.nfse.io',

            // Dedicated lookup hosts.
            'legal-entity' => 'https://api-legalentity.nfe.io',
            'natural-person' => 'https://api-naturalperson.nfe.io',

            // Sandbox uses the same hosts but with sandbox-scoped credentials. NFE.io does
            // not currently expose distinct sandbox subdomains; the API key determines
            // routing on the server side.
            default => 'https://api.nfe.io',
        };
    }
}
