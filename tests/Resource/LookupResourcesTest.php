<?php

declare(strict_types=1);

use Nfe\Client;
use Nfe\Config;
use Nfe\Exception\InvalidRequestException;
use Nfe\Http\Response;
use Nfe\Http\RetryPolicy;
use Nfe\Resource\Dto\Addresses\AddressLookupResponse;
use Nfe\Resource\Dto\ConsumerInvoiceQuery\TaxCoupon;
use Nfe\Resource\Dto\NaturalPersonLookup\NaturalPersonStatus;
use Nfe\Resource\Dto\StateTaxes\NfeStateTax;
use Nfe\Resource\Dto\TaxCodes\TaxCodePaginatedResponse;
use Nfe\Tests\Support\MockTransport;

function buildLookupClient(MockTransport $mock): Client
{
    return new Client(config: new Config(apiKey: 'k', retry: RetryPolicy::none(), transport: $mock));
}

it('Addresses routes to address.api.nfe.io/v2 (version embedded)', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '{"addresses":[]}'));
    buildLookupClient($mock)->addresses->lookupByPostalCode('01310-100');

    $sent = $mock->lastRequest();
    expect($sent?->baseUrl)->toBe('https://address.api.nfe.io/v2');
    expect($sent?->path)->toBe('/addresses/01310100');
});

it('Addresses returns AddressLookupResponse with addresses array', function (): void {
    $mock = (new MockTransport())->push(new Response(
        200,
        [],
        '{"addresses":[{"street":"Av. Paulista","city":"São Paulo"}]}',
    ));
    $result = buildLookupClient($mock)->addresses->lookupByPostalCode('01310100');

    expect($result)->toBeInstanceOf(AddressLookupResponse::class);
    expect($result->addresses)->toHaveCount(1);
    expect($result->addresses[0]['street'])->toBe('Av. Paulista');
});

it('Addresses search forwards OData filter as $filter query', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '{"addresses":[]}'));
    buildLookupClient($mock)->addresses->search(['filter' => "city eq 'São Paulo'"]);

    $url = $mock->lastRequest()?->url() ?? '';
    expect($url)->toContain('%24filter=city');
});

it('Addresses rejects empty term', function (): void {
    expect(fn() => buildLookupClient(new MockTransport())->addresses->lookupByTerm('   '))
        ->toThrow(InvalidRequestException::class);
});

it('LegalEntityLookup routes to legalentity.api.nfe.io', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '{"legalEntity":{"name":"Acme"}}'));
    buildLookupClient($mock)->legalEntityLookup->getBasicInfo('12.345.678/0001-90');

    $sent = $mock->lastRequest();
    expect($sent?->baseUrl)->toBe('https://legalentity.api.nfe.io');
    expect($sent?->path)->toBe('/v2/legalentities/basicInfo/12345678000190');
});

it('LegalEntityLookup normalises state to uppercase', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '{}'));
    buildLookupClient($mock)->legalEntityLookup->getStateTaxInfo('sp', '12345678000190');

    expect($mock->lastRequest()?->path)->toBe('/v2/legalentities/stateTaxInfo/SP/12345678000190');
});

it('LegalEntityLookup rejects unknown state', function (): void {
    expect(fn() => buildLookupClient(new MockTransport())->legalEntityLookup->getStateTaxInfo('ZZ', '12345678000190'))
        ->toThrow(InvalidRequestException::class);
});

it('NaturalPersonLookup routes to naturalperson.api.nfe.io', function (): void {
    $mock = (new MockTransport())->push(new Response(
        200,
        [],
        '{"name":"JOÃO","federalTaxNumber":"12345678901","status":"Regular"}',
    ));
    $result = buildLookupClient($mock)->naturalPersonLookup->getStatus('12345678901', '1990-01-15');

    $sent = $mock->lastRequest();
    expect($sent?->baseUrl)->toBe('https://naturalperson.api.nfe.io');
    expect($sent?->path)->toBe('/v1/naturalperson/status/12345678901/1990-01-15');
    expect($result)->toBeInstanceOf(NaturalPersonStatus::class);
    expect($result->status)->toBe('Regular');
});

it('NaturalPersonLookup accepts DateTimeImmutable', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '{}'));
    buildLookupClient($mock)->naturalPersonLookup->getStatus(
        '123.456.789-01',
        new DateTimeImmutable('1990-01-15 12:34:56'),
    );

    expect($mock->lastRequest()?->path)->toBe('/v1/naturalperson/status/12345678901/1990-01-15');
});

it('TaxCalculation routes to api.nfse.io with engine path', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '{"items":[]}'));
    $result = buildLookupClient($mock)->taxCalculation->calculate('tenant-123', [
        'operationType' => 'Outgoing',
        'items' => [['id' => 'x']],
    ]);

    $sent = $mock->lastRequest();
    expect($sent?->baseUrl)->toBe('https://api.nfse.io');
    expect($sent?->path)->toBe('/tax-rules/tenant-123/engine/calculate');
    expect($sent?->method)->toBe('POST');
    expect($result)->toBe(['items' => []]);
});

it('TaxCalculation rejects empty tenantId', function (): void {
    expect(fn() => buildLookupClient(new MockTransport())->taxCalculation->calculate('', ['operationType' => 'Outgoing', 'items' => [['id' => 'x']]]))
        ->toThrow(InvalidRequestException::class);
});

it('TaxCalculation rejects empty items', function (): void {
    expect(fn() => buildLookupClient(new MockTransport())->taxCalculation->calculate('t', ['operationType' => 'Outgoing', 'items' => []]))
        ->toThrow(InvalidRequestException::class);
});

it('TaxCodes uses page-style pagination 1-based', function (): void {
    $mock = (new MockTransport())->push(new Response(
        200,
        [],
        '{"items":[{"code":"X"}],"currentPage":2,"totalPages":5,"totalCount":48}',
    ));
    $result = buildLookupClient($mock)->taxCodes->listOperationCodes(['pageIndex' => 2, 'pageCount' => 10]);

    expect($result)->toBeInstanceOf(TaxCodePaginatedResponse::class);
    expect($result->currentPage)->toBe(2);
    expect($result->totalCount)->toBe(48);

    $url = $mock->lastRequest()?->url() ?? '';
    expect($url)->toContain('pageIndex=2');
    expect($url)->toContain('pageCount=10');
});

it('ProductInvoiceQuery routes to nfe.api.nfe.io v2', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '{"accessKey":"35261234567890123456789012345678901234567890"}'));
    buildLookupClient($mock)->productInvoiceQuery->retrieve('35261234567890123456789012345678901234567890');

    $sent = $mock->lastRequest();
    expect($sent?->baseUrl)->toBe('https://nfe.api.nfe.io');
    expect($sent?->path)->toBe('/v2/productinvoices/35261234567890123456789012345678901234567890');
});

it('ConsumerInvoiceQuery routes to nfe.api.nfe.io v1', function (): void {
    $mock = (new MockTransport())->push(new Response(
        200,
        [],
        '{"accessKey":"35261234567890123456789012345678901234567890","number":42}',
    ));
    $result = buildLookupClient($mock)->consumerInvoiceQuery->retrieve('35261234567890123456789012345678901234567890');

    $sent = $mock->lastRequest();
    expect($sent?->baseUrl)->toBe('https://nfe.api.nfe.io');
    expect($sent?->path)->toBe('/v1/consumerinvoices/coupon/35261234567890123456789012345678901234567890');
    expect($result)->toBeInstanceOf(TaxCoupon::class);
    expect($result->number)->toBe(42);
});

it('StateTaxes wraps create body as {stateTax: data}', function (): void {
    $mock = (new MockTransport())->push(new Response(
        201,
        [],
        '{"id":"st-1","taxNumber":"123456789","serie":1,"number":1,"code":"SP"}',
    ));
    $result = buildLookupClient($mock)->stateTaxes->create('co-1', [
        'taxNumber' => '123456789',
        'serie' => 1,
        'number' => 1,
        'code' => 'SP',
    ]);

    $body = $mock->lastRequest()?->body ?? '';
    $decoded = json_decode($body, true);
    expect($decoded)->toHaveKey('stateTax');
    expect($decoded['stateTax']['taxNumber'] ?? null)->toBe('123456789');

    expect($result)->toBeInstanceOf(NfeStateTax::class);
    expect($result->id)->toBe('st-1');
});

it('StateTaxes routes to api.nfse.io/v2', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '{}'));
    buildLookupClient($mock)->stateTaxes->retrieve('co-1', 'st-1');

    $sent = $mock->lastRequest();
    expect($sent?->baseUrl)->toBe('https://api.nfse.io');
    expect($sent?->path)->toBe('/v2/companies/co-1/statetaxes/st-1');
});
