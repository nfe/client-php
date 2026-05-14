<?php

declare(strict_types=1);

use Nfe\Config;
use Nfe\Environment;
use Nfe\Exception\InvalidRequestException;
use Nfe\Http\RetryPolicy;

it('accepts a non-empty apiKey', function () {
    $config = new Config(apiKey: 'k');
    expect($config->apiKey)->toBe('k');
    expect($config->environment)->toBe(Environment::Production);
    expect($config->timeout)->toBe(60);
});

it('rejects an empty apiKey', function () {
    expect(fn () => new Config(apiKey: ''))->toThrow(InvalidRequestException::class);
    expect(fn () => new Config(apiKey: '   '))->toThrow(InvalidRequestException::class);
});

it('rejects a non-positive timeout', function () {
    expect(fn () => new Config(apiKey: 'k', timeout: 0))->toThrow(InvalidRequestException::class);
    expect(fn () => new Config(apiKey: 'k', timeout: -1))->toThrow(InvalidRequestException::class);
});

it('exposes the retry policy', function () {
    $config = new Config(apiKey: 'k', retry: new RetryPolicy(maxRetries: 5));
    expect($config->retry->maxRetries)->toBe(5);
});

it('routes core API to api.nfe.io', function () {
    $config = new Config(apiKey: 'k');
    expect($config->baseUrlForApi('core'))->toBe('https://api.nfe.io');
    expect($config->baseUrlForApi('companies'))->toBe('https://api.nfe.io');
    expect($config->baseUrlForApi('webhooks'))->toBe('https://api.nfe.io');
});

it('routes CT-e to api.nfse.io', function () {
    $config = new Config(apiKey: 'k');
    expect($config->baseUrlForApi('cte'))->toBe('https://api.nfse.io');
    expect($config->baseUrlForApi('transportation'))->toBe('https://api.nfse.io');
});

it('routes lookups to their dedicated hosts', function () {
    $config = new Config(apiKey: 'k');
    expect($config->baseUrlForApi('legal-entity'))->toBe('https://api-legalentity.nfe.io');
    expect($config->baseUrlForApi('natural-person'))->toBe('https://api-naturalperson.nfe.io');
});

it('falls back to core for unknown families', function () {
    $config = new Config(apiKey: 'k');
    expect($config->baseUrlForApi('unknown-family'))->toBe('https://api.nfe.io');
});
