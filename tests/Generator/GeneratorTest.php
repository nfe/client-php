<?php

declare(strict_types=1);

use Nfe\Build\CheckMode;
use Nfe\Build\Generator;

beforeEach(function () {
    $this->tempRoot = sys_get_temp_dir() . '/nfe-sdk-gen-test-' . bin2hex(random_bytes(4));
    mkdir($this->tempRoot, 0o755, true);
});

afterEach(function () {
    if (isset($this->tempRoot) && is_dir($this->tempRoot)) {
        $stack = [$this->tempRoot];
        $files = [];
        while ($stack !== []) {
            $cur = array_pop($stack);
            foreach (scandir($cur) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                $abs = $cur . '/' . $entry;
                if (is_dir($abs)) {
                    $stack[] = $abs;
                    $files[] = $abs;
                } else {
                    @unlink($abs);
                }
            }
        }
        foreach (array_reverse($files) as $d) {
            @rmdir($d);
        }
        @rmdir($this->tempRoot);
    }
});

it('produces a file per schema under the spec namespace', function () {
    $generator = new Generator(
        specsDir:   __DIR__ . '/../fixtures/openapi',
        outputRoot: $this->tempRoot,
        rootNamespace: 'Nfe\\Generated',
    );

    $files = $generator->generate();

    expect($files)->toHaveKey('Minimal/Address.php');
    expect($files)->toHaveKey('Minimal/Borrower.php');
    expect($files)->toHaveKey('Minimal/FlowStatus.php');
    expect($files)->toHaveKey('Minimal/Invoice.php');
});

it('emits the AUTO-GENERATED header and namespace', function () {
    $generator = new Generator(
        specsDir:   __DIR__ . '/../fixtures/openapi',
        outputRoot: $this->tempRoot,
    );

    $files = $generator->generate();
    $address = $files['Minimal/Address.php'];

    expect($address)->toContain('AUTO-GENERATED');
    expect($address)->toContain('Source: tests/fixtures/openapi/minimal.yaml');
    expect($address)->toContain('Hash:   sha256:');
    expect($address)->toContain('namespace Nfe\\Generated\\Minimal;');
    expect($address)->toContain('final readonly class Address');
});

it('writes files to disk under outputRoot', function () {
    $generator = new Generator(
        specsDir:   __DIR__ . '/../fixtures/openapi',
        outputRoot: $this->tempRoot,
    );

    $written = $generator->writeTo($this->tempRoot);

    expect($written)->toContain('Minimal/Address.php');
    expect($this->tempRoot . '/Minimal/Address.php')->toBeFile();
});

it('CheckMode reports ok when disk matches', function () {
    $generator = new Generator(
        specsDir:   __DIR__ . '/../fixtures/openapi',
        outputRoot: $this->tempRoot,
    );

    $generator->writeTo($this->tempRoot);
    $diff = CheckMode::diff($generator, $this->tempRoot);

    expect($diff['ok'])->toBeTrue();
});

it('CheckMode reports drift when a file is tampered', function () {
    $generator = new Generator(
        specsDir:   __DIR__ . '/../fixtures/openapi',
        outputRoot: $this->tempRoot,
    );

    $generator->writeTo($this->tempRoot);
    file_put_contents($this->tempRoot . '/Minimal/Address.php', "<?php // tampered\n");

    $diff = CheckMode::diff($generator, $this->tempRoot);

    expect($diff['ok'])->toBeFalse();
    expect($diff['changed'])->toContain('Minimal/Address.php');
});
