<?php

declare(strict_types=1);

namespace Nfe;

use Nfe\Exception\InvalidRequestException;
use Nfe\Http\CurlTransport;
use Nfe\Http\Request;
use Nfe\Http\RequestOptions;
use Nfe\Http\Response;
use Nfe\Http\RetryingTransport;
use Nfe\Http\Transport;
use Nfe\Resource\AddressesResource;
use Nfe\Resource\CompaniesResource;
use Nfe\Resource\ConsumerInvoiceQueryResource;
use Nfe\Resource\ConsumerInvoicesResource;
use Nfe\Resource\InboundProductInvoicesResource;
use Nfe\Resource\LegalEntityLookupResource;
use Nfe\Resource\LegalPeopleResource;
use Nfe\Resource\NaturalPeopleResource;
use Nfe\Resource\NaturalPersonLookupResource;
use Nfe\Resource\ProductInvoiceQueryResource;
use Nfe\Resource\ProductInvoicesResource;
use Nfe\Resource\ServiceInvoicesResource;
use Nfe\Resource\StateTaxesResource;
use Nfe\Resource\TaxCalculationResource;
use Nfe\Resource\TaxCodesResource;
use Nfe\Resource\TransportationInvoicesResource;
use Nfe\Resource\WebhooksResource;
use Nfe\Util\UserAgent;

/**
 * Primary entry point for the NFE.io PHP SDK.
 *
 * Construct once per process or per tenant and reuse. Resources are bound
 * eagerly to public readonly properties; access them directly:
 *
 *     $nfe = new Nfe\Client(apiKey: $_ENV['NFE_API_KEY']);
 *     $invoice = $nfe->serviceInvoices->retrieve($companyId, $invoiceId);
 *
 * The SDK never mutates global state. All configuration lives on the
 * {@see Config} attached to this instance.
 */
final class Client
{
    public readonly Config $config;
    public readonly Transport $transport;

    // ------------------------------------------------------------------ //
    //  Resources (paridade 1:1 com o SDK Node.js)                        //
    // ------------------------------------------------------------------ //

    public readonly ServiceInvoicesResource $serviceInvoices;
    public readonly ProductInvoicesResource $productInvoices;
    public readonly ConsumerInvoicesResource $consumerInvoices;
    public readonly TransportationInvoicesResource $transportationInvoices;
    public readonly InboundProductInvoicesResource $inboundProductInvoices;
    public readonly ProductInvoiceQueryResource $productInvoiceQuery;
    public readonly ConsumerInvoiceQueryResource $consumerInvoiceQuery;
    public readonly CompaniesResource $companies;
    public readonly LegalPeopleResource $legalPeople;
    public readonly NaturalPeopleResource $naturalPeople;
    public readonly WebhooksResource $webhooks;
    public readonly AddressesResource $addresses;
    public readonly LegalEntityLookupResource $legalEntityLookup;
    public readonly NaturalPersonLookupResource $naturalPersonLookup;
    public readonly TaxCalculationResource $taxCalculation;
    public readonly TaxCodesResource $taxCodes;
    public readonly StateTaxesResource $stateTaxes;

    /**
     * @param string|null      $apiKey          Convenience: when present, builds the Config for you.
     * @param string|null      $dataApiKey      Optional separate key for data-services
     *                                            (CEP/CNPJ/CPF/NF-e query). Ignored if `$config` is set.
     *                                            Mirrors Node SDK dataApiKey.
     * @param Config|null      $config          Provide an explicit Config to override every default.
     * @param Environment      $environment     Defaults to Production. Ignored if `$config` is set.
     * @param int              $timeout         Per-request timeout in seconds. Ignored if `$config` is set.
     * @param Transport|null   $transport       Inject a custom transport (PSR-18 adapter, mock, etc.). Ignored if `$config` is set.
     * @param string|null      $userAgentSuffix Optional integrator identifier appended to User-Agent.
     */
    public function __construct(
        ?string $apiKey = null,
        ?string $dataApiKey = null,
        ?Config $config = null,
        Environment $environment = Environment::Production,
        int $timeout = 60,
        ?Transport $transport = null,
        ?string $userAgentSuffix = null,
    ) {
        if ($config === null) {
            if ($apiKey === null) {
                throw new InvalidRequestException(
                    'Nfe\\Client: provide either $apiKey or $config to instantiate the client.',
                );
            }
            $config = new Config(
                apiKey: $apiKey,
                dataApiKey: $dataApiKey,
                environment: $environment,
                timeout: $timeout,
                transport: $transport,
                userAgentSuffix: $userAgentSuffix,
            );
        }

        $this->config    = $config;
        $this->transport = $this->buildTransport($config);

        // Bind resources eagerly. They each just keep a reference to $this.
        $this->serviceInvoices        = new ServiceInvoicesResource($this);
        $this->productInvoices        = new ProductInvoicesResource($this);
        $this->consumerInvoices       = new ConsumerInvoicesResource($this);
        $this->transportationInvoices = new TransportationInvoicesResource($this);
        $this->inboundProductInvoices = new InboundProductInvoicesResource($this);
        $this->productInvoiceQuery    = new ProductInvoiceQueryResource($this);
        $this->consumerInvoiceQuery   = new ConsumerInvoiceQueryResource($this);
        $this->companies              = new CompaniesResource($this);
        $this->legalPeople            = new LegalPeopleResource($this);
        $this->naturalPeople          = new NaturalPeopleResource($this);
        $this->webhooks               = new WebhooksResource($this);
        $this->addresses              = new AddressesResource($this);
        $this->legalEntityLookup      = new LegalEntityLookupResource($this);
        $this->naturalPersonLookup    = new NaturalPersonLookupResource($this);
        $this->taxCalculation         = new TaxCalculationResource($this);
        $this->taxCodes               = new TaxCodesResource($this);
        $this->stateTaxes             = new StateTaxesResource($this);
    }

    /**
     * Send a {@see Request}, applying SDK-level concerns (auth header,
     * User-Agent, default timeout) and routing through the configured
     * (and retry-wrapped) transport.
     *
     * Resources call this through their `AbstractResource` helpers; consumers
     * use {@see self::request()} for endpoints not covered by a resource yet.
     */
    public function send(Request $request, ?RequestOptions $options = null): Response
    {
        // `??` suppresses property access on a null object, so `->` is correct here even
        // when $options is null. PHPStan flags `?->` as redundant in this exact pattern.
        $apiKey = $options->apiKey ?? $this->config->apiKey;

        $headers = $request->headers;

        if (!$this->hasHeader($headers, 'Authorization')) {
            $headers['Authorization'] = 'Basic ' . $apiKey;
        }
        if (!$this->hasHeader($headers, 'User-Agent')) {
            $headers['User-Agent'] = UserAgent::build($this->config->userAgentSuffix);
        }
        if (!$this->hasHeader($headers, 'Accept')) {
            $headers['Accept'] = 'application/json';
        }

        $authoritative = new Request(
            method: $request->method,
            baseUrl: $request->baseUrl,
            path: $request->path,
            headers: $headers,
            query: $request->query,
            body: $request->body,
            timeout: $request->timeout > 0 ? $request->timeout : $this->config->timeout,
        );

        return $this->transport->send($authoritative);
    }

    /**
     * Low-level escape hatch for endpoints not yet wrapped by a resource.
     *
     * @internal Reserved for future use and incidental needs. Prefer using
     *           the resource methods. The shape of this method may change.
     *
     * @param array<string, scalar|array<int, scalar>> $query
     * @param array<string, mixed>|null                $body
     */
    public function request(
        string $method,
        string $baseUrl,
        string $path,
        array $query = [],
        ?array $body = null,
        ?RequestOptions $options = null,
    ): Response {
        $request = new Request(
            method: $method,
            baseUrl: $baseUrl,
            path: $path,
            headers: [],
            query: $query,
            body: $body !== null ? json_encode($body, JSON_THROW_ON_ERROR) : null,
        );
        return $this->send($request, $options);
    }

    private function buildTransport(Config $config): Transport
    {
        $base = $config->transport ?? new CurlTransport(defaultTimeout: $config->timeout);
        if ($config->retry->maxRetries <= 0) {
            return $base;
        }
        return new RetryingTransport($base, $config->retry);
    }

    /**
     * @param array<string, string> $headers
     */
    private function hasHeader(array $headers, string $name): bool
    {
        $needle = strtolower($name);
        foreach (array_keys($headers) as $key) {
            if (strtolower((string) $key) === $needle) {
                return true;
            }
        }
        return false;
    }
}
