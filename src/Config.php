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
 *
 * **Two-key model**: NFE.io's platform separates billing between the main API
 * (emission, companies, webhooks — billed per document) and the data-services
 * API (CEP/CNPJ/CPF lookups, NF-e query — billed per query, often a different
 * plan). Some integrators have one key with both plans; others have two
 * distinct keys. {@see Config} accepts both via `apiKey` (main) and the
 * optional `dataApiKey`. When the data key is absent, the SDK falls back to
 * `apiKey` — matching the Node SDK's resolveDataApiKey behaviour exactly.
 */
final readonly class Config
{
    /**
     * @param string                $apiKey          NFE.io main API key. Required.
     * @param string|null           $dataApiKey      Optional separate key for data-services
     *                                                (CEP/CNPJ/CPF/NF-e query). Falls back to
     *                                                $apiKey when null. Mirrors Node SDK
     *                                                resolveDataApiKey().
     * @param Environment           $environment     Production or Sandbox routing.
     * @param int                   $timeout         Default per-request timeout in seconds.
     * @param RetryPolicy           $retry           Retry behavior for transient failures.
     * @param Transport|null        $transport       Override the default cURL transport. Null = use default.
     * @param LoggerInterface|null  $logger          Optional PSR-3 logger. PSR packages are NOT required at runtime.
     * @param string|null           $userAgentSuffix Optional integrator identifier appended to the SDK's User-Agent.
     */
    public function __construct(
        public string $apiKey,
        public ?string $dataApiKey = null,
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
        if ($dataApiKey !== null && trim($dataApiKey) === '') {
            throw new InvalidRequestException('Nfe\\Config: dataApiKey must be null or a non-empty string.');
        }
        if ($timeout <= 0) {
            throw new InvalidRequestException('Nfe\\Config: timeout must be positive.');
        }
    }

    /**
     * Resolve the base URL for a given NFE.io product family.
     *
     * The mapping mirrors the Node.js SDK's authoritative host routing exactly
     * (verified against `client-nodejs/src/core/client.ts` during c05). The
     * NFE.io platform exposes several distinct subdomains; each resource
     * declares its target via `AbstractResource::apiFamily()`.
     *
     * Hosts:
     *
     *  - `main`          → api.nfe.io  (companies, service invoices, legal/natural people, webhooks)
     *  - `addresses`     → address.api.nfe.io/v2  (postal-code lookup; version is part of the base URL)
     *  - `nfe-query`     → nfe.api.nfe.io  (NF-e and NFC-e query/download by access key)
     *  - `legal-entity`  → legalentity.api.nfe.io  (CNPJ lookup)
     *  - `natural-person`→ naturalperson.api.nfe.io  (CPF lookup)
     *  - `cte`           → api.nfse.io  (CT-e, inbound product, tax calculation, tax codes,
     *                                    product invoices, state taxes)
     *
     * Unknown families fall back to the `main` host as a safe default.
     */
    public function baseUrlForApi(string $api): string
    {
        return match ($api) {
            'main', 'companies', 'service-invoices', 'legal-people', 'natural-people', 'webhooks'
                => 'https://api.nfe.io',

            'addresses'
                => 'https://address.api.nfe.io/v2',

            'nfe-query'
                => 'https://nfe.api.nfe.io',

            'legal-entity'
                => 'https://legalentity.api.nfe.io',

            'natural-person'
                => 'https://naturalperson.api.nfe.io',

            'cte', 'transportation', 'inbound-product', 'tax-calculation', 'tax-codes',
            'product-invoices', 'consumer-invoices', 'state-taxes'
                => 'https://api.nfse.io',

            default
            => 'https://api.nfe.io',
        };
    }

    /**
     * Resolve which API key to use for a given API family.
     *
     * Data-services families (CEP/CNPJ/CPF lookup, NF-e/NFC-e query) use
     * `dataApiKey` when set; everything else uses `apiKey`. When `dataApiKey`
     * is null, data-services fall back to `apiKey` — matching the Node SDK
     * resolveDataApiKey() chain (`dataApiKey ?? apiKey`).
     *
     * If you see HTTP 403 on `addresses`/`legalEntityLookup`/`naturalPersonLookup`/
     * `productInvoiceQuery`/`consumerInvoiceQuery` despite a valid main key,
     * the most likely cause is that the main key's plan does not include the
     * data-services product. Pass a `dataApiKey` provisioned for the data
     * plan and the SDK routes the call accordingly.
     */
    public function apiKeyForApi(string $api): string
    {
        $usesDataKey = match ($api) {
            'addresses', 'legal-entity', 'natural-person', 'nfe-query' => true,
            default => false,
        };

        if ($usesDataKey && $this->dataApiKey !== null) {
            return $this->dataApiKey;
        }
        return $this->apiKey;
    }
}
