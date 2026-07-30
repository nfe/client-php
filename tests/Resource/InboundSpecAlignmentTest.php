<?php

declare(strict_types=1);

use Nfe\Resource\InboundProductInvoicesResource;
use Nfe\Resource\TransportationInvoicesResource;
use Symfony\Component\Yaml\Yaml;

/**
 * Amarra as rotas dos dois resources de inbound ao contrato canônico
 * `openapi/consulta-dfe-distribuicao-v2.yaml` (superconjunto das 3 specs de
 * distribuição) e pina as assinaturas públicas dos 20 métodos — a migração
 * fix-inbound-routes trocou 100% das rotas mantendo a API pública estável.
 */

/**
 * @return array<string, mixed> O spec OpenAPI completo, parseado.
 */
function loadDfeDistribuicaoSpec(): array
{
    static $spec = null;
    if ($spec === null) {
        $spec = Yaml::parseFile(__DIR__ . '/../../openapi/consulta-dfe-distribuicao-v2.yaml');
        expect($spec)->toBeArray();
    }

    return $spec;
}

it('every SDK inbound route exists in the DF-e v2 spec with the matching verb', function (): void {
    $paths = loadDfeDistribuicaoSpec()['paths'];

    // (template da spec, verbo) por método do SDK — espelho da tabela D2 do design.
    $expected = [
        // InboundProductInvoicesResource
        'enableAutoFetch'                => ['/v2/companies/{company_id}/inbound/productinvoices', 'post'],
        'disableAutoFetch'               => ['/v2/companies/{company_id}/inbound/productinvoices', 'delete'],
        'getSettings (NF-e)'             => ['/v2/companies/{company_id}/inbound/productinvoices', 'get'],
        'getDetails'                     => ['/v2/companies/{company_id}/inbound/{access_key}', 'get'],
        'getProductInvoiceDetails'       => ['/v2/companies/{company_id}/inbound/productinvoices/{access_key}', 'get'],
        'getEventDetails'                => ['/v2/companies/{company_id}/inbound/{access_key}/events/{event_key}', 'get'],
        'getProductInvoiceEventDetails'  => ['/v2/companies/{company_id}/inbound/productinvoices/{access_key}/events/{event_key}', 'get'],
        'getXml'                         => ['/v2/companies/{company_id}/inbound/{access_key}/xml', 'get'],
        'getEventXml'                    => ['/v2/companies/{company_id}/inbound/{access_key}/events/{event_key}/xml', 'get'],
        'getPdf'                         => ['/v2/companies/{company_id}/inbound/{access_key}/pdf', 'get'],
        'getJson'                        => ['/v2/companies/{company_id}/inbound/productinvoices/{access_key}/json', 'get'],
        'manifest'                       => ['/v2/companies/{company_id}/inbound/{access_key}/manifest', 'post'],
        'reprocessWebhook'               => ['/v2/companies/{company_id}/inbound/productinvoices/{access_key_or_nsu}/processwebhook', 'post'],
        // TransportationInvoicesResource
        'enable (CT-e)'                  => ['/v2/companies/{companyId}/inbound/transportationinvoices', 'post'],
        'disable (CT-e)'                 => ['/v2/companies/{companyId}/inbound/transportationinvoices', 'delete'],
        'getSettings (CT-e)'             => ['/v2/companies/{companyId}/inbound/transportationinvoices', 'get'],
        'retrieve (CT-e)'                => ['/v2/companies/{company_id}/inbound/{access_key}', 'get'],
        'downloadXml (CT-e)'             => ['/v2/companies/{company_id}/inbound/{access_key}/xml', 'get'],
        'getEvent (CT-e)'                => ['/v2/companies/{company_id}/inbound/{access_key}/events/{event_key}', 'get'],
        'downloadEventXml (CT-e)'        => ['/v2/companies/{company_id}/inbound/{access_key}/events/{event_key}/xml', 'get'],
    ];

    foreach ($expected as $method => [$template, $verb]) {
        expect(array_key_exists($template, $paths))->toBeTrue("rota de {$method} sumiu da spec");
        expect(array_key_exists($verb, $paths[$template] ?? []))->toBeTrue("verbo de {$method} mudou na spec");
    }
});

it('the dead legacy route schemes stay absent from the spec', function (): void {
    // /productinvoices/received/… , /productinvoices/inbound e /cte/… nunca
    // existiram no contrato; sondado morto em 2026-07-29. Se um sync de spec
    // os introduzir, reavaliar as rotas dos resources.
    $paths = array_keys(loadDfeDistribuicaoSpec()['paths']);

    foreach ($paths as $path) {
        expect($path)->not->toContain('/productinvoices/received');
        expect($path)->not->toContain('/cte/');
    }
});

it('manifest tpEvent is an integer query param in the spec (probed: literals rejected)', function (): void {
    $op = loadDfeDistribuicaoSpec()['paths']['/v2/companies/{company_id}/inbound/{access_key}/manifest']['post'];

    $tpEvent = null;
    foreach ($op['parameters'] as $param) {
        if (($param['name'] ?? '') === 'tpEvent') {
            $tpEvent = $param;
        }
    }
    expect($tpEvent)->toBeArray();
    expect($tpEvent['in'])->toBe('query');
    expect($tpEvent['schema']['type'])->toBe('integer');
});

it('keeps the public signatures of all 20 inbound methods stable', function (): void {
    $expected = [
        InboundProductInvoicesResource::class => [
            'enableAutoFetch'               => ['companyId', 'data', 'options'],
            'disableAutoFetch'              => ['companyId', 'options'],
            'getSettings'                   => ['companyId', 'options'],
            'getDetails'                    => ['companyId', 'accessKey', 'options'],
            'getProductInvoiceDetails'      => ['companyId', 'accessKey', 'options'],
            'getEventDetails'               => ['companyId', 'accessKey', 'eventKey', 'options'],
            'getProductInvoiceEventDetails' => ['companyId', 'accessKey', 'eventKey', 'options'],
            'getXml'                        => ['companyId', 'accessKey', 'options'],
            'getEventXml'                   => ['companyId', 'accessKey', 'eventKey', 'options'],
            'getPdf'                        => ['companyId', 'accessKey', 'options'],
            'getJson'                       => ['companyId', 'accessKey', 'options'],
            'manifest'                      => ['companyId', 'accessKey', 'manifestType', 'data', 'options'],
            'reprocessWebhook'              => ['companyId', 'accessKey', 'options'],
        ],
        TransportationInvoicesResource::class => [
            'enable'           => ['companyId', 'data', 'options'],
            'disable'          => ['companyId', 'options'],
            'getSettings'      => ['companyId', 'options'],
            'retrieve'         => ['companyId', 'accessKey', 'options'],
            'downloadXml'      => ['companyId', 'accessKey', 'options'],
            'getEvent'         => ['companyId', 'accessKey', 'eventKey', 'options'],
            'downloadEventXml' => ['companyId', 'accessKey', 'eventKey', 'options'],
        ],
    ];

    foreach ($expected as $class => $methods) {
        foreach ($methods as $method => $params) {
            $actual = array_map(
                fn(ReflectionParameter $p): string => $p->getName(),
                (new ReflectionMethod($class, $method))->getParameters(),
            );
            expect($actual)->toBe($params, "assinatura de {$class}::{$method} mudou");
        }
    }
});
