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

const TI_KEY = '35261234567890123456789012345678901234567890';

it('TransportationInvoices routes to api.nfse.io v2', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '{"enabled":true}'));
    buildTiClient($mock)->transportationInvoices->getSettings('abc');

    $sent = $mock->lastRequest();
    expect($sent?->baseUrl)->toBe('https://api.nfse.io');
    expect($sent?->path)->toBe('/v2/companies/abc/inbound/transportationinvoices');
});

// Verbo+path pinados por método — rotas /inbound/… da consulta-dfe-distribuicao-v2
// (as antigas /cte/… não existem na API; sondado 2026-07-29).
dataset('cte routes', [
    'enable (POST, era PUT)' => [
        fn(Client $c) => $c->transportationInvoices->enable('abc', ['environment' => 'Production']),
        'POST', '/v2/companies/abc/inbound/transportationinvoices', '{"enabled":true}',
    ],
    'disable' => [
        fn(Client $c) => $c->transportationInvoices->disable('abc'),
        'DELETE', '/v2/companies/abc/inbound/transportationinvoices', '{"enabled":false}',
    ],
    'getSettings' => [
        fn(Client $c) => $c->transportationInvoices->getSettings('abc'),
        'GET', '/v2/companies/abc/inbound/transportationinvoices', '{"enabled":true}',
    ],
    'retrieve (rota genérica /inbound/{key})' => [
        fn(Client $c) => $c->transportationInvoices->retrieve('abc', TI_KEY),
        'GET', '/v2/companies/abc/inbound/' . TI_KEY, '{}',
    ],
    'downloadXml' => [
        fn(Client $c) => $c->transportationInvoices->downloadXml('abc', TI_KEY),
        'GET', '/v2/companies/abc/inbound/' . TI_KEY . '/xml', '<xml/>',
    ],
    'getEvent' => [
        fn(Client $c) => $c->transportationInvoices->getEvent('abc', TI_KEY, 'evt-1'),
        'GET', '/v2/companies/abc/inbound/' . TI_KEY . '/events/evt-1', '{}',
    ],
    'downloadEventXml' => [
        fn(Client $c) => $c->transportationInvoices->downloadEventXml('abc', TI_KEY, 'evt-1'),
        'GET', '/v2/companies/abc/inbound/' . TI_KEY . '/events/evt-1/xml', '<xml/>',
    ],
]);

it('pins verb+path', function (callable $call, string $method, string $path, string $body): void {
    $mock = (new MockTransport())->push(new Response(200, [], $body));
    $call(buildTiClient($mock));

    expect($mock->lastRequest()?->method)->toBe($method);
    expect($mock->lastRequest()?->path)->toBe($path);
})->with('cte routes');

it('retrieve normalises accessKey (strips spaces)', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '{}'));
    buildTiClient($mock)->transportationInvoices->retrieve(
        'abc',
        '3526 1234 5678 9012 3456 7890 1234 5678 9012 3456 7890',
    );

    expect($mock->lastRequest()?->path)->toBe('/v2/companies/abc/inbound/' . TI_KEY);
});

it('rejects malformed access keys', function (): void {
    expect(fn() => buildTiClient(new MockTransport())->transportationInvoices->retrieve('abc', '123'))
        ->toThrow(InvalidRequestException::class);
});

it('downloadEventXml returns raw bytes', function (): void {
    $xml = '<?xml version="1.0"?><event/>';
    $mock = (new MockTransport())->push(new Response(200, [], $xml));
    $bytes = buildTiClient($mock)->transportationInvoices->downloadEventXml('abc', TI_KEY, 'evt-1');

    expect($bytes)->toBe($xml);
});
