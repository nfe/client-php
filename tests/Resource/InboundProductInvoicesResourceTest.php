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

const IPI_KEY = '35261234567890123456789012345678901234567890';

it('InboundProductInvoices routes to api.nfse.io v2', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '{"enabled":true}'));
    buildIpiClient($mock)->inboundProductInvoices->getSettings('abc');

    $sent = $mock->lastRequest();
    expect($sent?->baseUrl)->toBe('https://api.nfse.io');
    expect($sent?->path)->toBe('/v2/companies/abc/inbound/productinvoices');
});

// Verbo+path pinados por método — rotas do contrato /inbound/… da
// consulta-dfe-distribuicao-v2, confirmadas vivas por sonda (2026-07-29/30).
// As rotas antigas (/productinvoices/received/…, /productinvoices/inbound)
// não existem na API (404 de rota / colisão com /productinvoices/{id}).
dataset('inbound nfe routes', [
    'enableAutoFetch (POST, era PUT)' => [
        fn(Client $c) => $c->inboundProductInvoices->enableAutoFetch('abc', ['environment' => 'Production']),
        'POST', '/v2/companies/abc/inbound/productinvoices', '{"enabled":true}',
    ],
    'disableAutoFetch' => [
        fn(Client $c) => $c->inboundProductInvoices->disableAutoFetch('abc'),
        'DELETE', '/v2/companies/abc/inbound/productinvoices', '{"enabled":false}',
    ],
    'getSettings' => [
        fn(Client $c) => $c->inboundProductInvoices->getSettings('abc'),
        'GET', '/v2/companies/abc/inbound/productinvoices', '{"enabled":true}',
    ],
    'getDetails (rota genérica)' => [
        fn(Client $c) => $c->inboundProductInvoices->getDetails('abc', IPI_KEY),
        'GET', '/v2/companies/abc/inbound/' . IPI_KEY, '{}',
    ],
    'getProductInvoiceDetails' => [
        fn(Client $c) => $c->inboundProductInvoices->getProductInvoiceDetails('abc', IPI_KEY),
        'GET', '/v2/companies/abc/inbound/productinvoices/' . IPI_KEY, '{}',
    ],
    'getEventDetails (rota genérica)' => [
        fn(Client $c) => $c->inboundProductInvoices->getEventDetails('abc', IPI_KEY, 'evt-1'),
        'GET', '/v2/companies/abc/inbound/' . IPI_KEY . '/events/evt-1', '{}',
    ],
    'getProductInvoiceEventDetails' => [
        fn(Client $c) => $c->inboundProductInvoices->getProductInvoiceEventDetails('abc', IPI_KEY, 'evt-1'),
        'GET', '/v2/companies/abc/inbound/productinvoices/' . IPI_KEY . '/events/evt-1', '{}',
    ],
    'getXml (rota genérica)' => [
        fn(Client $c) => $c->inboundProductInvoices->getXml('abc', IPI_KEY),
        'GET', '/v2/companies/abc/inbound/' . IPI_KEY . '/xml', '<xml/>',
    ],
    'getEventXml (rota genérica)' => [
        fn(Client $c) => $c->inboundProductInvoices->getEventXml('abc', IPI_KEY, 'evt-1'),
        'GET', '/v2/companies/abc/inbound/' . IPI_KEY . '/events/evt-1/xml', '<xml/>',
    ],
    'getPdf (rota genérica)' => [
        fn(Client $c) => $c->inboundProductInvoices->getPdf('abc', IPI_KEY),
        'GET', '/v2/companies/abc/inbound/' . IPI_KEY . '/pdf', '%PDF-1.4',
    ],
    'getJson' => [
        fn(Client $c) => $c->inboundProductInvoices->getJson('abc', IPI_KEY),
        'GET', '/v2/companies/abc/inbound/productinvoices/' . IPI_KEY . '/json', '{}',
    ],
    'manifest (POST + tpEvent em query, era PUT /manifest/{type})' => [
        fn(Client $c) => $c->inboundProductInvoices->manifest('abc', IPI_KEY, '210210'),
        'POST', '/v2/companies/abc/inbound/' . IPI_KEY . '/manifest?tpEvent=210210', '{}',
    ],
    'reprocessWebhook (processwebhook, era /webhook/reprocess)' => [
        fn(Client $c) => $c->inboundProductInvoices->reprocessWebhook('abc', IPI_KEY),
        'POST', '/v2/companies/abc/inbound/productinvoices/' . IPI_KEY . '/processwebhook', '{}',
    ],
]);

it('pins verb+path', function (callable $call, string $method, string $path, string $body): void {
    $mock = (new MockTransport())->push(new Response(200, [], $body));
    $call(buildIpiClient($mock));

    expect($mock->lastRequest()?->method)->toBe($method);
    expect($mock->lastRequest()?->path)->toBe($path);
})->with('inbound nfe routes');

it('manifest maps the legacy literals to SEFAZ numeric codes', function (): void {
    // Sondado 2026-07-30: o binder da API só aceita tpEvent numérico
    // ("The value 'Confirmation' is not valid.").
    $map = [
        'Confirmation'    => '210200',
        'Acknowledgement' => '210210',
        'Unknown'         => '210220',
        'Refused'         => '210240',
    ];
    foreach ($map as $literal => $code) {
        $mock = (new MockTransport())->push(new Response(200, [], '{}'));
        buildIpiClient($mock)->inboundProductInvoices->manifest('abc', IPI_KEY, $literal);
        expect($mock->lastRequest()?->path)
            ->toBe('/v2/companies/abc/inbound/' . IPI_KEY . '/manifest?tpEvent=' . $code);
    }
});

it('manifest rejects an empty or unknown manifestType', function (string $bad): void {
    expect(fn() => buildIpiClient(new MockTransport())->inboundProductInvoices->manifest('abc', IPI_KEY, $bad))
        ->toThrow(InvalidRequestException::class);
})->with(['   ', 'Ciencia', '21021']);

it('reprocessWebhook accepts an NSU instead of a 44-digit access key', function (): void {
    $mock = (new MockTransport())->push(new Response(202, [], '{}'));
    buildIpiClient($mock)->inboundProductInvoices->reprocessWebhook('abc', '123456789');

    expect($mock->lastRequest()?->path)
        ->toBe('/v2/companies/abc/inbound/productinvoices/123456789/processwebhook');
});

it('reprocessWebhook rejects digit counts that are neither key nor NSU', function (): void {
    expect(fn() => buildIpiClient(new MockTransport())->inboundProductInvoices->reprocessWebhook(
        'abc',
        '12345678901234567890', // 20 dígitos: nem chave (44) nem NSU (1–15)
    ))->toThrow(InvalidRequestException::class);
});

it('getPdf returns raw bytes', function (): void {
    $pdf = "%PDF-1.4\nfake-payload";
    $mock = (new MockTransport())->push(new Response(200, [], $pdf));
    $bytes = buildIpiClient($mock)->inboundProductInvoices->getPdf('abc', IPI_KEY);

    expect($bytes)->toBe($pdf);
});
