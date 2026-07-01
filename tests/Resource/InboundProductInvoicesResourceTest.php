<?php

declare(strict_types=1);

use Nfe\Client;
use Nfe\Config;
use Nfe\Exception\InvalidRequestException;
use Nfe\Http\Response;
use Nfe\Http\RetryPolicy;
use Nfe\Tests\Support\MockTransport;

function buildIpiClient(MockTransport $mock): Client
{
    return new Client(config: new Config(apiKey: 'k', retry: RetryPolicy::none(), transport: $mock));
}

it('InboundProductInvoices routes to api.nfse.io v2', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '{"enabled":true}'));
    buildIpiClient($mock)->inboundProductInvoices->getSettings('abc');

    $sent = $mock->lastRequest();
    expect($sent?->baseUrl)->toBe('https://api.nfse.io');
    expect($sent?->path)->toBe('/v2/companies/abc/productinvoices/inbound');
});

it('getDetails hits received/{accessKey}', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '{}'));
    buildIpiClient($mock)->inboundProductInvoices->getDetails(
        'abc',
        '35261234567890123456789012345678901234567890',
    );

    expect($mock->lastRequest()?->path)
        ->toBe('/v2/companies/abc/productinvoices/received/35261234567890123456789012345678901234567890');
});

it('manifest requires a non-empty manifestType', function (): void {
    expect(fn() => buildIpiClient(new MockTransport())->inboundProductInvoices->manifest(
        'abc',
        '35261234567890123456789012345678901234567890',
        '   ',
    ))->toThrow(InvalidRequestException::class);
});

it('getPdf returns raw bytes', function (): void {
    $pdf = "%PDF-1.4\nfake-payload";
    $mock = (new MockTransport())->push(new Response(200, [], $pdf));
    $bytes = buildIpiClient($mock)->inboundProductInvoices->getPdf(
        'abc',
        '35261234567890123456789012345678901234567890',
    );

    expect($bytes)->toBe($pdf);
});

it('reprocessWebhook is a POST', function (): void {
    $mock = (new MockTransport())->push(new Response(202, [], '{}'));
    buildIpiClient($mock)->inboundProductInvoices->reprocessWebhook(
        'abc',
        '35261234567890123456789012345678901234567890',
    );

    expect($mock->lastRequest()?->method)->toBe('POST');
});
