<?php

declare(strict_types=1);

use Nfe\Client;
use Nfe\Config;
use Nfe\Exception\InvalidRequestException;
use Nfe\Exception\NotFoundException;
use Nfe\Http\Response;
use Nfe\Http\RetryPolicy;
use Nfe\Resource\Dto\ServiceInvoices\ServiceInvoice;
use Nfe\Response\ServiceInvoiceIssued;
use Nfe\Response\ServiceInvoicePending;
use Nfe\Tests\Support\MockTransport;

function buildSvcClient(MockTransport $mock): Client
{
    return new Client(config: new Config(
        apiKey: 'k',
        retry: RetryPolicy::none(),
        transport: $mock,
    ));
}

it('returns Pending when API responds 202 with Location', function (): void {
    $mock = (new MockTransport())->push(new Response(
        202,
        ['location' => '/v1/companies/abc/serviceinvoices/inv-001'],
        '',
    ));
    $client = buildSvcClient($mock);

    $result = $client->serviceInvoices->create('abc', ['servicesAmount' => 100.0]);

    expect($result)->toBeInstanceOf(ServiceInvoicePending::class);
    expect($result->invoiceId())->toBe('inv-001');
});

it('returns Issued when API responds 201 with body', function (): void {
    $mock = (new MockTransport())->push(new Response(
        201,
        [],
        '{"id":"inv-001","status":"Issued","flowStatus":"Issued","environment":"Production"}',
    ));
    $client = buildSvcClient($mock);

    $result = $client->serviceInvoices->create('abc', ['servicesAmount' => 100.0]);

    expect($result)->toBeInstanceOf(ServiceInvoiceIssued::class);
    $dto = $result->resource();
    expect($dto)->toBeInstanceOf(ServiceInvoice::class);
    expect($dto->id)->toBe('inv-001');
    expect($dto->flowStatus)->toBe('Issued');
});

it('rejects empty companyId synchronously', function (): void {
    $mock = new MockTransport();
    $client = buildSvcClient($mock);

    expect(fn() => $client->serviceInvoices->retrieve('', 'inv'))
        ->toThrow(InvalidRequestException::class);
    // No HTTP call should have been made.
    expect($mock->sent())->toHaveCount(0);
});

it('routes to api.nfe.io/v1', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '{"id":"x"}'));
    $client = buildSvcClient($mock);

    $client->serviceInvoices->retrieve('abc', 'inv-001');

    $sent = $mock->lastRequest();
    expect($sent?->baseUrl)->toBe('https://api.nfe.io');
    expect($sent?->path)->toBe('/v1/companies/abc/serviceinvoices/inv-001');
    expect($sent?->method)->toBe('GET');
});

it('surfaces 404 as NotFoundException', function (): void {
    $mock = (new MockTransport())
        ->push(new Response(404, [], '{"message":"not found"}'));
    $client = buildSvcClient($mock);

    expect(fn() => $client->serviceInvoices->retrieve('abc', 'inv-x'))
        ->toThrow(NotFoundException::class);
});

it('downloadPdf returns raw bytes', function (): void {
    $pdfBytes = "%PDF-1.4 fake-pdf-payload\n";
    $mock = (new MockTransport())->push(new Response(200, [], $pdfBytes));
    $client = buildSvcClient($mock);

    $bytes = $client->serviceInvoices->downloadPdf('abc', 'inv-001');

    expect($bytes)->toBe($pdfBytes);
});

it('cancel issues DELETE and returns updated DTO', function (): void {
    $mock = (new MockTransport())->push(new Response(
        200,
        [],
        '{"id":"inv-001","status":"Cancelled","flowStatus":"Cancelled"}',
    ));
    $client = buildSvcClient($mock);

    $result = $client->serviceInvoices->cancel('abc', 'inv-001');

    expect($result->flowStatus)->toBe('Cancelled');
    expect($mock->lastRequest()?->method)->toBe('DELETE');
});

it('list returns ListResponse hydrated from serviceInvoices wrapper', function (): void {
    $payload = json_encode([
        'serviceInvoices' => [
            ['id' => 'a', 'flowStatus' => 'Issued'],
            ['id' => 'b', 'flowStatus' => 'WaitingSend'],
        ],
        'pageIndex' => 1,
        'pageCount' => 2,
    ]);
    $mock = (new MockTransport())->push(new Response(200, [], (string) $payload));
    $client = buildSvcClient($mock);

    $list = $client->serviceInvoices->list('abc', ['pageCount' => 2]);

    expect($list->data)->toHaveCount(2);
    expect($list->data[0]->id)->toBe('a');
    expect($list->page->pageIndex)->toBe(1);
    expect($list->page->pageCount)->toBe(2);
});
