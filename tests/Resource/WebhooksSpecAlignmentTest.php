<?php

declare(strict_types=1);

use Nfe\Resource\Dto\Webhooks\AccountWebhook;
use Symfony\Component\Yaml\Yaml;

/**
 * Amarra o DTO AccountWebhook ao schema de /v2/webhooks em
 * openapi/nf-servico-v1.yaml: um sync de spec que mude o contrato quebra o
 * build em vez de driftar silenciosamente (o codegen atual não emite os paths
 * de webhook, então o alinhamento é verificado por parse direto do YAML).
 */

/**
 * @return array<string, mixed> O spec OpenAPI completo, parseado.
 */
function loadServiceInvoiceSpec(): array
{
    static $spec = null;
    if ($spec === null) {
        $spec = Yaml::parseFile(__DIR__ . '/../../openapi/nf-servico-v1.yaml');
        expect($spec)->toBeArray();
    }

    return $spec;
}

it('POST /v2/webhooks request body requires the webHook envelope', function (): void {
    $spec = loadServiceInvoiceSpec();

    $requestSchema = $spec['paths']['/v2/webhooks']['post']['requestBody']['content']['application/json']['schema'] ?? null;
    expect($requestSchema)->toBeArray();
    expect($requestSchema['properties'])->toHaveKey('webHook');
});

it('AccountWebhook covers every field of the /v2/webhooks item schema', function (): void {
    $spec = loadServiceInvoiceSpec();

    $itemSchema = $spec['paths']['/v2/webhooks']['get']['responses']['200']['content']['application/json']['schema']['properties']['webHooks']['items'] ?? null;
    expect($itemSchema)->toBeArray();

    $specFields = array_keys($itemSchema['properties']);
    expect($specFields)->not->toBeEmpty();

    $dtoProperties = array_map(
        fn(ReflectionProperty $p): string => $p->getName(),
        (new ReflectionClass(AccountWebhook::class))->getProperties(),
    );

    expect(array_diff($specFields, $dtoProperties))->toBeEmpty();
});

it('pins the deliberate wire deviations: contentType/status are int enums in the spec but strings on the wire', function (): void {
    // A API serializa strings ("json", "Active"), mas o spec declara enum int
    // (0/1) — o DTO segue o fio. Este teste PINA o enum int: se um sync do
    // spec o corrigir para string, esta falha é o sinal para tipar o DTO
    // direto do spec e remover o desvio documentado.
    $spec = loadServiceInvoiceSpec();

    $itemSchema = $spec['paths']['/v2/webhooks']['get']['responses']['200']['content']['application/json']['schema']['properties']['webHooks']['items'];

    foreach (['contentType', 'status'] as $field) {
        $declared = $itemSchema['properties'][$field];
        expect($declared['type'])->toBe('integer', "spec mudou o tipo de {$field} — reavaliar o desvio no DTO");
        expect($declared['enum'])->toBe([0, 1]);

        $dtoType = (new ReflectionProperty(AccountWebhook::class, $field))->getType();
        assert($dtoType instanceof ReflectionNamedType);
        expect($dtoType->getName())->toBe('string');
    }
});
