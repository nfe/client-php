<?php

declare(strict_types=1);

use Nfe\Client;
use Nfe\Config;
use Nfe\Exception\InvalidRequestException;
use Nfe\Http\Response;
use Nfe\Http\RetryPolicy;
use Nfe\Resource\Dto\ConsumerInvoices\ConsumerInvoice;
use Nfe\Response\ConsumerInvoiceIssued;
use Nfe\Response\ConsumerInvoicePending;
use Nfe\Tests\Support\MockTransport;

function buildCiClient(MockTransport $mock): Client
{
    return new Client(config: new Config(apiKey: 'k', retry: RetryPolicy::none(), transport: $mock));
}

it('ConsumerInvoices routes to api.nfse.io v2', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '{}'));
    buildCiClient($mock)->consumerInvoices->retrieve('co-1', 'inv-1');

    $sent = $mock->lastRequest();
    expect($sent?->baseUrl)->toBe('https://api.nfse.io');
    expect($sent?->path)->toBe('/v2/companies/co-1/consumerinvoices/inv-1');
});

it('create returns Issued for 201', function (): void {
    $mock = (new MockTransport())->push(new Response(
        201,
        [],
        '{"id":"inv-9","status":"Issued","accessKey":"35261234567890123456789012345678901234567890","number":42}',
    ));
    $result = buildCiClient($mock)->consumerInvoices->create('co-1', ['borrower' => []]);

    expect($result)->toBeInstanceOf(ConsumerInvoiceIssued::class);
    /** @var ConsumerInvoiceIssued $result */
    expect($result->resource())->toBeInstanceOf(ConsumerInvoice::class);
    expect($result->resource()->id)->toBe('inv-9');
    expect($result->resource()->number)->toBe(42);
});

it('create returns Pending for 202 with Location', function (): void {
    $mock = (new MockTransport())->push(new Response(
        202,
        ['location' => '/v2/companies/co-1/consumerinvoices/inv-pending-123'],
        '',
    ));
    $result = buildCiClient($mock)->consumerInvoices->create('co-1', []);

    expect($result)->toBeInstanceOf(ConsumerInvoicePending::class);
    /** @var ConsumerInvoicePending $result */
    expect($result->invoiceId())->toBe('inv-pending-123');
});

it('createWithStateTax POSTs to /statetaxes/{stid}/consumerinvoices', function (): void {
    $mock = (new MockTransport())->push(new Response(201, [], '{"id":"x"}'));
    buildCiClient($mock)->consumerInvoices->createWithStateTax('co-1', 'st-1', []);

    expect($mock->lastRequest()?->path)
        ->toBe('/v2/companies/co-1/statetaxes/st-1/consumerinvoices');
});

it('cancel is DELETE on /{invoiceId}', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '{"id":"inv-1","flowStatus":"Cancelled"}'));
    $result = buildCiClient($mock)->consumerInvoices->cancel('co-1', 'inv-1');

    expect($mock->lastRequest()?->method)->toBe('DELETE');
    expect($mock->lastRequest()?->path)->toBe('/v2/companies/co-1/consumerinvoices/inv-1');
    expect($result->flowStatus)->toBe('Cancelled');
});

it('list hits the right path with consumerInvoices wrapper', function (): void {
    $payload = json_encode([
        'consumerInvoices' => [['id' => 'a'], ['id' => 'b']],
        'pageIndex' => 1,
        'pageCount' => 50,
    ]);
    $mock = (new MockTransport())->push(new Response(200, [], (string) $payload));
    $list = buildCiClient($mock)->consumerInvoices->list('co-1');

    expect($list->data)->toHaveCount(2);
    expect($list->data[0]->id)->toBe('a');
    expect($mock->lastRequest()?->path)->toBe('/v2/companies/co-1/consumerinvoices');
});

it('downloadPdf returns raw bytes', function (): void {
    $pdf = "%PDF-1.4\nfake-payload";
    $mock = (new MockTransport())->push(new Response(200, [], $pdf));
    $bytes = buildCiClient($mock)->consumerInvoices->downloadPdf('co-1', 'inv-1');

    expect($bytes)->toBe($pdf);
    expect($mock->lastRequest()?->path)->toBe('/v2/companies/co-1/consumerinvoices/inv-1/pdf');
});

it('downloadRejectionXml hits /xml/rejection path', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '<xml/>'));
    buildCiClient($mock)->consumerInvoices->downloadRejectionXml('co-1', 'inv-1');

    expect($mock->lastRequest()?->path)->toBe('/v2/companies/co-1/consumerinvoices/inv-1/xml/rejection');
});

it('disableRange is POST to /consumerinvoices/disablement', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '{"ok":true}'));
    buildCiClient($mock)->consumerInvoices->disableRange('co-1', ['from' => 1, 'to' => 10]);

    expect($mock->lastRequest()?->method)->toBe('POST');
    expect($mock->lastRequest()?->path)->toBe('/v2/companies/co-1/consumerinvoices/disablement');
});

it('validates inputs (empty companyId throws)', function (): void {
    expect(fn() => buildCiClient(new MockTransport())->consumerInvoices->retrieve('', 'inv-1'))
        ->toThrow(InvalidRequestException::class);
});
