<?php

declare(strict_types=1);

/**
 * OpenAPI -> PHP types generator.
 *
 * Reads every *.yaml under openapi/ and emits typed DTOs and enums under
 * src/Generated/<NamespaceSuffix>/.
 *
 * Usage:
 *   composer generate                  # write src/Generated/ from openapi/*.yaml
 *   composer generate:check            # exit non-zero if src/Generated/ would change
 *   php scripts/generate.php           # same as composer generate
 *   php scripts/generate.php --check   # same as composer generate:check
 */

require __DIR__ . '/../vendor/autoload.php';

use Nfe\Build\CheckMode;
use Nfe\Build\Generator;
use Nfe\Build\Linter;

$repoRoot   = dirname(__DIR__);
$specsDir   = $repoRoot . '/openapi';
$outputRoot = $repoRoot . '/src/Generated';

$checkMode = in_array('--check', $argv, true);

$generator = new Generator(
    specsDir: $specsDir,
    outputRoot: $outputRoot,
);

$files = $generator->generate();

// Per-spec summary: no spec is skipped silently. Swagger 2.0 specs are
// expected to emit nothing; an OpenAPI 3.x spec emitting nothing is a
// possible sync regression and gets a highlighted warning (but not a
// failure — the syntactic lint below is what fails builds).
fwrite(STDOUT, "Specs:\n");
foreach ($generator->specSummary($files) as $row) {
    if ($row['emitted'] === 0 && $row['swagger2']) {
        fwrite(STDOUT, sprintf("  %-40s 0 schemas (Swagger 2.0 — out of the generator's reach)\n", $row['spec']));
    } elseif ($row['emitted'] === 0) {
        fwrite(STDOUT, sprintf("  %-40s ⚠ WARNING: OpenAPI 3.x spec emitted 0 schemas (possible sync regression)\n", $row['spec']));
    } else {
        fwrite(STDOUT, sprintf("  %-40s %d schema file(s)\n", $row['spec'], $row['emitted']));
    }
}

// Syntactic lint: every emitted file must parse. This is the hard gate that
// keeps invalid PHP (e.g., unsanitised dotted type-hints) out of the dist.
$lintErrors = Linter::lint($files);
if ($lintErrors !== []) {
    fwrite(STDERR, "✗ Generator emitted syntactically invalid PHP:\n");
    foreach ($lintErrors as $error) {
        fwrite(STDERR, "  {$error}\n");
    }
    exit(1);
}

if ($checkMode) {
    $diff = CheckMode::diff($generator, $outputRoot, $files);
    if ($diff['ok']) {
        fwrite(STDOUT, "✓ src/Generated/ is in sync with openapi/\n");
        exit(0);
    }

    fwrite(STDERR, "✗ src/Generated/ is OUT OF SYNC with openapi/\n");
    foreach ($diff['added'] as $f) {
        fwrite(STDERR, "  + would add:    {$f}\n");
    }
    foreach ($diff['removed'] as $f) {
        fwrite(STDERR, "  - would remove: {$f}\n");
    }
    foreach ($diff['changed'] as $f) {
        fwrite(STDERR, "  ~ would change: {$f}\n");
    }
    fwrite(STDERR, "\nRun 'composer generate' to regenerate.\n");
    exit(1);
}

$written = $generator->writeTo($outputRoot, $files);
fwrite(STDOUT, "✓ Generated " . count($written) . " file(s) under src/Generated/\n");
exit(0);
