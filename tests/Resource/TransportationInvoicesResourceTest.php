<?php

declare(strict_types=1);

use Nfe\Client;
use Nfe\Config;
use Nfe\Exception\InvalidRequestException;
use Nfe\Http\Response;
use Nfe\Http\RetryPolicy;
use Nfe\Tests\Support\MockTransport;

function buildTiClient(MockTransport $mock): Client
{
    return new Client(config: new Config(apiKey: 'k', retry: RetryPolicy::none(), transport: $mock));
}

it('TransportationInvoices routes to api.nfse.io v2', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '{"enabled":true}'));
    buildTiClient($mock)->transportationInvoices->getSettings('abc');

    $sent = $mock->lastRequest();
    expect($sent?->baseUrl)->toBe('https://api.nfse.io');
    expect($sent?->path)->toBe('/v2/companies/abc/cte/inbound');
});

it('retrieve normalises accessKey and hits the right path', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '{}'));
    buildTiClient($mock)->transportationInvoices->retrieve(
        'abc',
        '3526 1234 5678 9012 3456 7890 1234 5678 9012 3456 7890',
    );

    expect($mock->lastRequest()?->path)
        ->toBe('/v2/companies/abc/cte/35261234567890123456789012345678901234567890');
});

it('rejects malformed access keys', function (): void {
    expect(fn() => buildTiClient(new MockTransport())->transportationInvoices->retrieve('abc', '123'))
        ->toThrow(InvalidRequestException::class);
});

it('downloadEventXml returns raw bytes from the right path', function (): void {
    $xml = '<?xml version="1.0"?><event/>';
    $mock = (new MockTransport())->push(new Response(200, [], $xml));
    $bytes = buildTiClient($mock)->transportationInvoices->downloadEventXml(
        'abc',
        '35261234567890123456789012345678901234567890',
        'evt-1',
    );

    expect($bytes)->toBe($xml);
    expect($mock->lastRequest()?->path)
        ->toBe('/v2/companies/abc/cte/35261234567890123456789012345678901234567890/events/evt-1/xml');
});
