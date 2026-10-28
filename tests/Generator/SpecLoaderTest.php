<?php

declare(strict_types=1);

use Nfe\Build\Generator;
use Nfe\Build\SpecLoader;
use Nfe\Build\YamlNormalizer;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

// Multi-line quoted scalars whose continuation lines start at column 0, the
// shape the upstream NF-e specs use for the "Indisponível no momento"
// authority-event descriptions. symfony/yaml rejects it as-is.
const QUOTED_CONTINUATION_FIXTURE = __DIR__ . '/../fixtures/openapi-quoted-continuation/quoted-continuation-v1.yaml';

it('reproduces the symfony/yaml rejection on the raw fixture', function (): void {
    expect(fn() => Yaml::parse((string) file_get_contents(QUOTED_CONTINUATION_FIXTURE)))
        ->toThrow(ParseException::class, 'Malformed inline YAML string');
});

it('loads a spec with column-0 quoted continuations and folds them like a lenient parser', function (): void {
    $loader = SpecLoader::fromFile(QUOTED_CONTINUATION_FIXTURE);
    $op = $loader->raw['paths']['/v2/companies/{companyId}/productinvoices/{id}/authority-events']['post'];

    // Values match PyYAML/libyaml on the same file.
    expect($op['description'])->toBe(
        "### Informações adicionais\r\n⛔ **Indisponível no momento.** Recurso protegido por feature flag.\n"
        . '| `type` | tpEvento | |---|---| - item: com dois-pontos e "aspas" escapadas',
    );
    expect($op['tags'])->toBe(['Nota Fiscal de Produto']);
    expect($op['responses'])->toBe(['200' => ['description' => 'OK']]);

    $schema = $loader->schemas()['AuthorityEvent'];
    expect($schema['description'])->toBe('Evento de autoridade. Indisponível no momento.');
    expect($schema['properties']['type']['description'])->toBe("Tipo do evento; 'aspas' simples continuam aqui");
    expect($schema['properties']['note']['description'])->toBe("Bloco literal com \"aspas que não fecham\nfica intacto\n");
    expect($schema['required'])->toBe(['type']);

    expect($loader->reindentedLines)->toBe(7);
});

it('hashes the bytes on disk, not the normalised text', function (): void {
    $loader = SpecLoader::fromFile(QUOTED_CONTINUATION_FIXTURE);

    expect($loader->hash)->toBe(hash_file('sha256', QUOTED_CONTINUATION_FIXTURE));
});

it('does not touch a spec symfony/yaml already parses', function (): void {
    $loader = SpecLoader::fromFile(__DIR__ . '/../fixtures/openapi/minimal.yaml');

    expect($loader->reindentedLines)->toBe(0);
});

it('still fails loud, with the spec path, when re-indenting does not help', function (): void {
    $path = sys_get_temp_dir() . '/nfe-sdk-broken-' . bin2hex(random_bytes(4)) . '.yaml';
    file_put_contents($path, "openapi: 3.0.1\npaths: {\n");

    try {
        expect(fn() => SpecLoader::fromFile($path))->toThrow(RuntimeException::class, $path);
    } finally {
        @unlink($path);
    }
});

it('generates the schema from the fixture with the folded description', function (): void {
    $generator = new Generator(
        specsDir: dirname(QUOTED_CONTINUATION_FIXTURE),
        outputRoot: sys_get_temp_dir() . '/nfe-sdk-unused',
    );

    $files = $generator->generate();

    expect($files)->toHaveKey('QuotedContinuationV1/AuthorityEvent.php');
    expect($files['QuotedContinuationV1/AuthorityEvent.php'])
        ->toContain(' * Evento de autoridade. Indisponível no momento.');
    expect($generator->specSummary($files)[0]['reindented'])->toBe(7);
});

it('re-indents only continuation lines at or left of the owning key', function (): void {
    $yaml = "a:\n  b:\n    description: \"um\ndois\n      tres\n\n  quatro\"\n    other: 1\n";

    [$out, $count] = YamlNormalizer::reindentQuotedContinuations($yaml);

    expect($count)->toBe(2);
    expect($out)->toBe("a:\n  b:\n    description: \"um\n      dois\n      tres\n\n      quatro\"\n    other: 1\n");
    expect(Yaml::parse($out)['a']['b'])->toBe(['description' => "um dois tres\nquatro", 'other' => 1]);
});

it('leaves block scalars, closed quotes and bare quoted lines alone', function (): void {
    $yaml = <<<'YAML'
        a:
          literal: |
            title: "nao fecha
          folded: >-
            - "nao fecha
          closed: "fecha \" aqui"
          single: 'fecha '' aqui'
          plain: texto
            "continuacao plain
        b: 1

        YAML;

    [$out, $count] = YamlNormalizer::reindentQuotedContinuations($yaml);

    expect($count)->toBe(0);
    expect($out)->toBe($yaml);
});

it('handles sequence entries and keys inside sequence entries', function (): void {
    $yaml = "tags:\n  - \"um\ndois\"\nitems:\n  - name: 'tres\n    quatro'\n";

    [$out, $count] = YamlNormalizer::reindentQuotedContinuations($yaml);

    expect($count)->toBe(2);
    expect(Yaml::parse($out))->toBe([
        'tags'  => ['um dois'],
        'items' => [['name' => 'tres quatro']],
    ]);
});
