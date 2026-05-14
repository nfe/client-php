<?php

declare(strict_types=1);

use Nfe\Config;
use Nfe\Environment;
use Nfe\Exception\InvalidRequestException;
use Nfe\Http\RetryPolicy;

it('accepts a non-empty apiKey', function (): void {
    $config = new Config(apiKey: 'k');
    expect($config->apiKey)->toBe('k');
    expect($config->environment)->toBe(Environment::Production);
    expect($config->timeout)->toBe(60);
});

it('rejects an empty apiKey', function (): void {
    expect(fn() => new Config(apiKey: ''))->toThrow(InvalidRequestException::class);
    expect(fn() => new Config(apiKey: '   '))->toThrow(InvalidRequestException::class);
});

it('rejects a non-positive timeout', function (): void {
    expect(fn() => new Config(apiKey: 'k', timeout: 0))->toThrow(InvalidRequestException::class);
    expect(fn() => new Config(apiKey: 'k', timeout: -1))->toThrow(InvalidRequestException::class);
});

it('exposes the retry policy', function (): void {
    $config = new Config(apiKey: 'k', retry: new RetryPolicy(maxRetries: 5));
    expect($config->retry->maxRetries)->toBe(5);
});

it('routes the main API family to api.nfe.io', function (): void {
    $config = new Config(apiKey: 'k');
    expect($config->baseUrlForApi('main'))->toBe('https://api.nfe.io');
    expect($config->baseUrlForApi('companies'))->toBe('https://api.nfe.io');
    expect($config->baseUrlForApi('service-invoices'))->toBe('https://api.nfe.io');
    expect($config->baseUrlForApi('webhooks'))->toBe('https://api.nfe.io');
    expect($config->baseUrlForApi('legal-people'))->toBe('https://api.nfe.io');
    expect($config->baseUrlForApi('natural-people'))->toBe('https://api.nfe.io');
});

it('routes addresses to its dedicated host with embedded version', function (): void {
    expect((new Config(apiKey: 'k'))->baseUrlForApi('addresses'))->toBe('https://address.api.nfe.io/v2');
});

it('routes NF-e and NFC-e queries to nfe.api.nfe.io', function (): void {
    expect((new Config(apiKey: 'k'))->baseUrlForApi('nfe-query'))->toBe('https://nfe.api.nfe.io');
});

it('routes CNPJ lookup to legalentity.api.nfe.io', function (): void {
    expect((new Config(apiKey: 'k'))->baseUrlForApi('legal-entity'))->toBe('https://legalentity.api.nfe.io');
});

it('routes CPF lookup to naturalperson.api.nfe.io', function (): void {
    expect((new Config(apiKey: 'k'))->baseUrlForApi('natural-person'))->toBe('https://naturalperson.api.nfe.io');
});

it('routes cte and shared nfse families to api.nfse.io', function (): void {
    $c = new Config(apiKey: 'k');
    expect($c->baseUrlForApi('cte'))->toBe('https://api.nfse.io');
    expect($c->baseUrlForApi('transportation'))->toBe('https://api.nfse.io');
    expect($c->baseUrlForApi('inbound-product'))->toBe('https://api.nfse.io');
    expect($c->baseUrlForApi('tax-calculation'))->toBe('https://api.nfse.io');
    expect($c->baseUrlForApi('tax-codes'))->toBe('https://api.nfse.io');
    expect($c->baseUrlForApi('product-invoices'))->toBe('https://api.nfse.io');
    expect($c->baseUrlForApi('state-taxes'))->toBe('https://api.nfse.io');
});

it('falls back to api.nfe.io for unknown families', function (): void {
    expect((new Config(apiKey: 'k'))->baseUrlForApi('unknown-future-family'))->toBe('https://api.nfe.io');
});
