<?php

declare(strict_types=1);

use Nfe\Build\Generator;

beforeEach(function (): void {
    $this->tempRoot = sys_get_temp_dir() . '/nfe-sdk-summary-test-' . bin2hex(random_bytes(4));
    $this->specsDir = $this->tempRoot . '/openapi';
    mkdir($this->specsDir, 0o755, true);

    file_put_contents($this->specsDir . '/with-schemas-v1.yaml', <<<'YAML'
        openapi: 3.0.1
        info: { title: With Schemas, version: "1.0" }
        paths: {}
        components:
          schemas:
            Borrower:
              type: object
              required: [name]
              properties:
                name: { type: string }
        YAML);

    file_put_contents($this->specsDir . '/legacy-swagger-v1.yaml', <<<'YAML'
        swagger: "2.0"
        info: { title: Legacy, version: "1.0" }
        paths: {}
        YAML);

    file_put_contents($this->specsDir . '/empty-openapi3-v1.yaml', <<<'YAML'
        openapi: 3.0.1
        info: { title: Empty, version: "1.0" }
        paths: {}
        YAML);
});

afterEach(function (): void {
    foreach (glob($this->specsDir . '/*.yaml') ?: [] as $f) {
        @unlink($f);
    }
    @rmdir($this->specsDir);
    @rmdir($this->tempRoot);
});

it('reports every spec with its emitted count, flagging Swagger 2.0 as such', function (): void {
    $generator = new Generator(
        specsDir: $this->specsDir,
        outputRoot: $this->tempRoot . '/out',
    );

    $summary = $generator->specSummary();
    $byName = array_column($summary, null, 'spec');

    expect($byName)->toHaveCount(3);

    expect($byName['with-schemas-v1.yaml']['emitted'])->toBe(1);
    expect($byName['with-schemas-v1.yaml']['swagger2'])->toBeFalse();

    // Swagger 2.0: zero output is expected — informational, not a regression.
    expect($byName['legacy-swagger-v1.yaml']['emitted'])->toBe(0);
    expect($byName['legacy-swagger-v1.yaml']['swagger2'])->toBeTrue();

    // OpenAPI 3.x with zero schemas: the summary must expose it (warning-worthy)
    // while the generator itself still completes without error.
    expect($byName['empty-openapi3-v1.yaml']['emitted'])->toBe(0);
    expect($byName['empty-openapi3-v1.yaml']['swagger2'])->toBeFalse();
});

it('accepts a precomputed file map and does not regenerate', function (): void {
    $generator = new Generator(
        specsDir: $this->specsDir,
        outputRoot: $this->tempRoot . '/out',
    );

    $files = $generator->generate();
    $summary = $generator->specSummary($files);

    expect(array_column($summary, 'emitted', 'spec'))->toBe([
        'empty-openapi3-v1.yaml'  => 0,
        'legacy-swagger-v1.yaml'  => 0,
        'with-schemas-v1.yaml'    => 1,
    ]);
});
