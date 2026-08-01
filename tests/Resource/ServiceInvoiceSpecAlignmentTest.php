<?php

declare(strict_types=1);

use Nfe\Resource\Dto\ServiceInvoices\Borrower;
use Nfe\Resource\Dto\ServiceInvoices\ServiceInvoice;
use Symfony\Component\Yaml\Yaml;

/**
 * Amarra o DTO ServiceInvoice (e o aninhado Borrower) ao schema de resposta da
 * NFS-e em openapi/nf-servico-v1.yaml. Como a resposta de sucesso é declarada
 * INLINE (sem schema nomeado), o codegen não a cobre — o alinhamento é
 * verificado por parse direto do YAML.
 *
 * IMPORTANTE: ancorado pelo PATH `/serviceinvoices/{id}`, NÃO pelo operationId —
 * `ServiceInvoices_idGet` colide entre `/{id}` e `/external/{id}` neste spec.
 */

/** @return array<string, mixed> Propriedades da resposta 200 do retrieve. */
function nfServicoRetrieveProps(): array
{
    static $props = null;
    if ($props === null) {
        $spec = Yaml::parseFile(__DIR__ . '/../../openapi/nf-servico-v1.yaml');
        $props = $spec['paths']['/v1/companies/{company_id}/serviceinvoices/{id}']['get']['responses']['200']['content']['application/json']['schema']['properties'] ?? null;
        expect($props)->toBeArray();
    }

    return $props;
}

/**
 * @param class-string $dto
 * @return list<string>
 */
function dtoConstructorFields(string $dto): array
{
    return array_map(
        fn(ReflectionParameter $p): string => $p->getName(),
        (new ReflectionClass($dto))->getConstructor()->getParameters(),
    );
}

it('ServiceInvoice typed fields are a subset of the retrieve response schema', function (): void {
    $specFields = array_keys(nfServicoRetrieveProps());

    // `raw` é o escape-hatch (não é campo do fio); `totalAmount` é o phantom
    // deprecado (ver o teste abaixo). Todo o resto DEVE existir no spec.
    $typed = array_diff(dtoConstructorFields(ServiceInvoice::class), ['raw', 'totalAmount']);

    expect(array_values(array_diff($typed, $specFields)))->toBe([]);
});

it('Borrower typed fields are a subset of the spec borrower schema', function (): void {
    $borrowerProps = nfServicoRetrieveProps()['borrower']['properties'] ?? null;
    expect($borrowerProps)->toBeArray();

    $typed = array_diff(dtoConstructorFields(Borrower::class), ['raw']);

    expect(array_values(array_diff($typed, array_keys($borrowerProps))))->toBe([]);
});

it('pins totalAmount as a phantom field absent from the spec', function (): void {
    // totalAmount é @deprecated exatamente porque a API nunca o retorna. Se um
    // sync de spec passar a declará-lo, esta falha é o sinal para reavaliar a
    // deprecação (tipar de verdade a partir do spec).
    expect(array_keys(nfServicoRetrieveProps()))->not->toContain('totalAmount');
});

it('cancellation-xml path+verb exists in nf-servico-v1 (ServiceInvoices_GetCancellationXml)', function (): void {
    $spec = Yaml::parseFile(__DIR__ . '/../../openapi/nf-servico-v1.yaml');
    $op = $spec['paths']['/v1/companies/{company_id}/serviceinvoices/{id}/cancellation-xml']['get'] ?? null;

    expect($op)->toBeArray();
    expect($op['operationId'] ?? null)->toBe('ServiceInvoices_GetCancellationXml');
});

it('cancellation-xml path+verb exists in service-invoice-rtc-v1 (declared with :param style)', function (): void {
    $spec = Yaml::parseFile(__DIR__ . '/../../openapi/service-invoice-rtc-v1.yaml');

    // A spec RTC declara parâmetros no estilo `:param`; normalizamos para
    // `{param}` antes de comparar com a rota que o SDK emite.
    $normalized = [];
    foreach (array_keys($spec['paths'] ?? []) as $path) {
        $normalized[] = preg_replace('/:([A-Za-z_]+)/', '{$1}', (string) $path);
    }

    expect($normalized)->toContain('/v1/companies/{company_id}/serviceinvoices/{id}/cancellation-xml');
    expect($spec['paths']['/v1/companies/:company_id/serviceinvoices/:id/cancellation-xml']['get'] ?? null)->toBeArray();
});

it('the retrieve path exists and its operationId collides (why we anchor by path)', function (): void {
    $spec = Yaml::parseFile(__DIR__ . '/../../openapi/nf-servico-v1.yaml');
    $retrieveId = $spec['paths']['/v1/companies/{company_id}/serviceinvoices/{id}']['get']['operationId'] ?? null;
    $externalId = $spec['paths']['/v1/companies/{company_id}/serviceinvoices/external/{id}']['get']['operationId'] ?? null;

    // Documenta a colisão: por isso o alinhamento acima resolve por PATH.
    expect($retrieveId)->toBe($externalId);
});
