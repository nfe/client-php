<?php

declare(strict_types=1);

use Nfe\Client;
use Nfe\Config;
use Nfe\Environment;
use Nfe\Exception\InvalidRequestException;
use Nfe\Http\Request;
use Nfe\Http\Response;
use Nfe\Http\RetryPolicy;
use Nfe\Resource\ServiceInvoicesResource;
use Nfe\Tests\Support\MockTransport;

it('builds a client from just an apiKey', function (): void {
    $client = new Client(apiKey: 'k');
    expect($client->config->apiKey)->toBe('k');
    expect($client->config->environment)->toBe(Environment::Production);
});

it('throws when neither apiKey nor config are provided', function (): void {
    expect(fn() => new Client())->toThrow(InvalidRequestException::class);
});

it('accepts a full Config object', function (): void {
    $config = new Config(apiKey: 'k', environment: Environment::Sandbox, timeout: 120);
    $client = new Client(config: $config);
    expect($client->config)->toBe($config);
    expect($client->config->timeout)->toBe(120);
});

it('exposes all 17 resource properties typed correctly', function (): void {
    $client = new Client(apiKey: 'k');

    expect($client->serviceInvoices)->toBeInstanceOf(ServiceInvoicesResource::class);
    expect($client->productInvoices)->toBeInstanceOf(Nfe\Resource\ProductInvoicesResource::class);
    expect($client->consumerInvoices)->toBeInstanceOf(Nfe\Resource\ConsumerInvoicesResource::class);
    expect($client->transportationInvoices)->toBeInstanceOf(Nfe\Resource\TransportationInvoicesResource::class);
    expect($client->inboundProductInvoices)->toBeInstanceOf(Nfe\Resource\InboundProductInvoicesResource::class);
    expect($client->productInvoiceQuery)->toBeInstanceOf(Nfe\Resource\ProductInvoiceQueryResource::class);
    expect($client->consumerInvoiceQuery)->toBeInstanceOf(Nfe\Resource\ConsumerInvoiceQueryResource::class);
    expect($client->companies)->toBeInstanceOf(Nfe\Resource\CompaniesResource::class);
    expect($client->legalPeople)->toBeInstanceOf(Nfe\Resource\LegalPeopleResource::class);
    expect($client->naturalPeople)->toBeInstanceOf(Nfe\Resource\NaturalPeopleResource::class);
    expect($client->webhooks)->toBeInstanceOf(Nfe\Resource\WebhooksResource::class);
    expect($client->addresses)->toBeInstanceOf(Nfe\Resource\AddressesResource::class);
    expect($client->legalEntityLookup)->toBeInstanceOf(Nfe\Resource\LegalEntityLookupResource::class);
    expect($client->naturalPersonLookup)->toBeInstanceOf(Nfe\Resource\NaturalPersonLookupResource::class);
    expect($client->taxCalculation)->toBeInstanceOf(Nfe\Resource\TaxCalculationResource::class);
    expect($client->taxCodes)->toBeInstanceOf(Nfe\Resource\TaxCodesResource::class);
    expect($client->stateTaxes)->toBeInstanceOf(Nfe\Resource\StateTaxesResource::class);
});

it('injects Authorization and User-Agent into outgoing requests', function (): void {
    $mock = new MockTransport();
    $mock->push(new Response(200, [], '{}'));

    $config = new Config(
        apiKey: 'my-key',
        retry: RetryPolicy::none(),
        transport: $mock,
    );
    $client = new Client(config: $config);

    $client->send(new Request('GET', 'https://api.nfe.io', '/v1/x'));

    $sent = $mock->lastRequest();
    expect($sent?->headers['Authorization'])->toBe('Basic my-key');
    expect($sent?->headers['User-Agent'])->toStartWith('Nfe-PHP/');
});

it('appends the userAgentSuffix to the User-Agent header', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '{}'));

    $config = new Config(
        apiKey: 'k',
        retry: RetryPolicy::none(),
        transport: $mock,
        userAgentSuffix: 'WHMCS/8.10',
    );
    $client = new Client(config: $config);

    $client->send(new Request('GET', 'https://api.nfe.io', '/v1/x'));

    expect($mock->lastRequest()?->headers['User-Agent'])->toEndWith('WHMCS/8.10');
});

it('does not overwrite Authorization or User-Agent when caller already set them', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '{}'));

    $config = new Config(
        apiKey: 'k',
        retry: RetryPolicy::none(),
        transport: $mock,
    );
    $client = new Client(config: $config);

    $client->send(new Request(
        method: 'GET',
        baseUrl: 'https://api.nfe.io',
        path: '/v1/x',
        headers: ['Authorization' => 'Bearer override', 'User-Agent' => 'custom'],
    ));

    expect($mock->lastRequest()?->headers['Authorization'])->toBe('Bearer override');
    expect($mock->lastRequest()?->headers['User-Agent'])->toBe('custom');
});
