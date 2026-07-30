<?php

declare(strict_types=1);

use Symfony\Component\Yaml\Yaml;

/**
 * Amarra as rotas de inutilização e EPEC de ProductInvoicesResource ao contrato
 * de openapi/nf-produto-v2.yaml: um sync de spec que mova esses paths quebra o
 * build em vez de driftar silenciosamente. Rotas confirmadas por sonda ao vivo
 * em 2026-07-29 (vault review-07-14-2026/09 — Sondas 2026-07-29).
 */

/**
 * @return array<string, mixed> O spec OpenAPI completo, parseado.
 */
function loadProductInvoiceSpec(): array
{
    static $spec = null;
    if ($spec === null) {
        $spec = Yaml::parseFile(__DIR__ . '/../../openapi/nf-produto-v2.yaml');
        expect($spec)->toBeArray();
    }

    return $spec;
}

it('collective disablement is POST /v2/companies/{companyId}/productinvoices/disablement', function (): void {
    $spec = loadProductInvoiceSpec();

    $op = $spec['paths']['/v2/companies/{companyId}/productinvoices/disablement']['post'] ?? null;
    expect($op)->toBeArray();

    // O body segue o schema DisablementResource, que declara `reason`
    // (obrigatório no fio: 400 "The Reason field is required", sondado 2026-07-29).
    $ref = $op['requestBody']['content']['application/json']['schema']['$ref'] ?? null;
    expect($ref)->toBe('#/components/schemas/DisablementResource');
    expect($spec['components']['schemas']['DisablementResource']['properties'])->toHaveKey('reason');
});

it('per-invoice disablement is POST …/{invoiceId}/disablement with reason as query param', function (): void {
    $spec = loadProductInvoiceSpec();

    $op = $spec['paths']['/v2/companies/{companyId}/productinvoices/{invoiceId}/disablement']['post'] ?? null;
    expect($op)->toBeArray();

    $queryParams = array_column(
        array_filter($op['parameters'] ?? [], fn(array $p): bool => ($p['in'] ?? '') === 'query'),
        'name',
    );
    expect($queryParams)->toContain('reason');
    expect($op)->not->toHaveKey('requestBody');
});

it('EPEC XML download is GET …/{invoiceId}/xml-epec (hyphenated)', function (): void {
    $spec = loadProductInvoiceSpec();

    expect($spec['paths']['/v2/companies/{companyId}/productinvoices/{invoiceId}/xml-epec'] ?? null)
        ->toBeArray()->toHaveKey('get');
});

it('the dead PUT …/disable and GET …/xml/epec routes stay absent from the spec', function (): void {
    // Sondado 2026-07-29: 405 / 404 de rota inexistente. Se um sync de spec os
    // ressuscitar, reavaliar as rotas do resource.
    $paths = array_keys(loadProductInvoiceSpec()['paths']);

    expect($paths)->not->toContain('/v2/companies/{companyId}/productinvoices/disable');
    expect($paths)->not->toContain('/v2/companies/{companyId}/productinvoices/{invoiceId}/disable');
    expect($paths)->not->toContain('/v2/companies/{companyId}/productinvoices/{invoiceId}/xml/epec');
});
