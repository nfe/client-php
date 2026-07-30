<?php

declare(strict_types=1);

use Nfe\Client;
use Nfe\Config;
use Nfe\Exception\InvalidRequestException;
use Nfe\Http\Response;
use Nfe\Http\RetryPolicy;
use Nfe\Resource\Dto\ProductInvoices\ProductInvoice;
use Nfe\Response\ProductInvoiceIssued;
use Nfe\Response\ProductInvoicePending;
use Nfe\Tests\Support\MockTransport;

function buildPiClient(MockTransport $mock): Client
{
    return new Client(config: new Config(
        apiKey: 'k',
        retry: RetryPolicy::none(),
        transport: $mock,
    ));
}

it('routes ProductInvoices to api.nfse.io v2', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '{"id":"x"}'));
    $client = buildPiClient($mock);

    $client->productInvoices->retrieve('abc', 'inv-001');

    $sent = $mock->lastRequest();
    expect($sent?->baseUrl)->toBe('https://api.nfse.io');
    expect($sent?->path)->toBe('/v2/companies/abc/productinvoices/inv-001');
});

it('create returns Pending on 202', function (): void {
    $mock = (new MockTransport())->push(new Response(
        202,
        ['location' => '/v2/companies/abc/productinvoices/inv-001'],
        '',
    ));
    $result = buildPiClient($mock)->productInvoices->create('abc', ['number' => 1]);

    expect($result)->toBeInstanceOf(ProductInvoicePending::class);
    expect($result->invoiceId())->toBe('inv-001');
});

it('create returns Issued on 201', function (): void {
    $mock = (new MockTransport())->push(new Response(
        201,
        [],
        '{"id":"x","accessKey":"35261234567890123456789012345678901234567890","flowStatus":"Issued"}',
    ));
    $result = buildPiClient($mock)->productInvoices->create('abc', []);

    expect($result)->toBeInstanceOf(ProductInvoiceIssued::class);
    expect($result->resource())->toBeInstanceOf(ProductInvoice::class);
    expect($result->resource()->id)->toBe('x');
});

it('createWithStateTax uses the statetaxes-scoped path', function (): void {
    $mock = (new MockTransport())->push(new Response(201, [], '{"id":"x"}'));
    buildPiClient($mock)->productInvoices->createWithStateTax('abc', 'st-1', []);

    expect($mock->lastRequest()?->path)
        ->toBe('/v2/companies/abc/statetaxes/st-1/productinvoices');
});

it('list passes cursor-style options through', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '{"productInvoices":[]}'));
    buildPiClient($mock)->productInvoices->list('abc', [
        'environment' => 'Production',
        'startingAfter' => 'cursor-x',
        'limit' => 50,
    ]);

    $url = $mock->lastRequest()?->url() ?? '';
    expect($url)->toContain('environment=Production');
    expect($url)->toContain('startingAfter=cursor-x');
    expect($url)->toContain('limit=50');
});

it('sendCorrectionLetter rejects empty correction text', function (): void {
    $mock = new MockTransport();
    expect(fn() => buildPiClient($mock)->productInvoices->sendCorrectionLetter('abc', 'inv', '  '))
        ->toThrow(InvalidRequestException::class);
});

it('downloadEpecXml returns raw bytes', function (): void {
    $xml = '<?xml version="1.0"?><EPEC/>';
    $mock = (new MockTransport())->push(new Response(200, [], $xml));
    $bytes = buildPiClient($mock)->productInvoices->downloadEpecXml('abc', 'inv-1');

    expect($bytes)->toBe($xml);
});

it('downloadEpecXml is GET to the hyphenated /xml-epec route', function (): void {
    // Sondado 2026-07-29: /xml/epec é 404 de rota inexistente; a rota viva é /xml-epec.
    $mock = (new MockTransport())->push(new Response(200, [], '<EPEC/>'));
    buildPiClient($mock)->productInvoices->downloadEpecXml('abc', 'inv-1');

    expect($mock->lastRequest()?->method)->toBe('GET');
    expect($mock->lastRequest()?->path)->toBe('/v2/companies/abc/productinvoices/inv-1/xml-epec');
});

it('downloadRejectionXml keeps the live /xml/rejection alias', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '<rej/>'));
    buildPiClient($mock)->productInvoices->downloadRejectionXml('abc', 'inv-1');

    expect($mock->lastRequest()?->path)->toBe('/v2/companies/abc/productinvoices/inv-1/xml/rejection');
});

it('disableRange is POST to /productinvoices/disablement', function (): void {
    // Sondado 2026-07-29: PUT …/disable → 405; a rota viva é POST …/disablement.
    $mock = (new MockTransport())->push(new Response(204, [], ''));
    buildPiClient($mock)->productInvoices->disableRange('co-1', [
        'serie' => 1,
        'beginNumber' => 10,
        'lastNumber' => 10,
        'reason' => 'quebra de sequência',
    ]);

    expect($mock->lastRequest()?->method)->toBe('POST');
    expect($mock->lastRequest()?->path)->toBe('/v2/companies/co-1/productinvoices/disablement');
    $body = json_decode($mock->lastRequest()?->body ?? 'null', associative: true);
    expect($body)->toMatchArray(['reason' => 'quebra de sequência', 'serie' => 1]);
});

it('disable is POST to /productinvoices/{id}/disablement with reason promoted to query', function (): void {
    // Spec: no endpoint individual, `reason` é query param (não há request body declarado).
    $mock = (new MockTransport())->push(new Response(204, [], ''));
    buildPiClient($mock)->productInvoices->disable('co-1', 'inv-1', ['reason' => 'nota em duplicidade']);

    $sent = $mock->lastRequest();
    expect($sent?->method)->toBe('POST');
    expect($sent?->path)->toBe('/v2/companies/co-1/productinvoices/inv-1/disablement?' . http_build_query(['reason' => 'nota em duplicidade']));
    expect($sent?->body)->toBeNull();
});

it('disable without reason posts to /disablement with no query', function (): void {
    $mock = (new MockTransport())->push(new Response(204, [], ''));
    buildPiClient($mock)->productInvoices->disable('co-1', 'inv-1', []);

    expect($mock->lastRequest()?->method)->toBe('POST');
    expect($mock->lastRequest()?->path)->toBe('/v2/companies/co-1/productinvoices/inv-1/disablement');
});
