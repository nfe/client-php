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

$repoRoot   = dirname(__DIR__);
$specsDir   = $repoRoot . '/openapi';
$outputRoot = $repoRoot . '/src/Generated';

$checkMode = in_array('--check', $argv, true);

$generator = new Generator(
    specsDir:   $specsDir,
    outputRoot: $outputRoot,
);

if ($checkMode) {
    $diff = CheckMode::diff($generator, $outputRoot);
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

$written = $generator->writeTo($outputRoot);
fwrite(STDOUT, "✓ Generated " . count($written) . " file(s) under src/Generated/\n");
exit(0);
