<?php

declare(strict_types=1);

namespace Nfe\Build;

/**
 * Orchestrator: turn a directory of OpenAPI specs into a directory of generated PHP files.
 *
 * One spec file produces one subdirectory under {@see self::$outputRoot}, named after
 * the NameMapper-derived namespace suffix. Each schema in the spec becomes one .php
 * file containing one class or enum.
 */
final class Generator
{
    /**
     * @param string $specsDir    Absolute path to the directory containing *.yaml specs.
     * @param string $outputRoot  Absolute path to the root of the generated tree (e.g., src/Generated).
     * @param string $rootNamespace PHP namespace under which generated subnamespaces live (e.g., "Nfe\\Generated").
     */
    public function __construct(
        public readonly string $specsDir,
        public readonly string $outputRoot,
        public readonly string $rootNamespace = 'Nfe\\Generated',
    ) {}

    /**
     * @return array<string, string> Map of relative file path => file contents that the generator
     *                               would write to disk. Caller decides whether to write or diff.
     */
    public function generate(): array
    {
        $files = [];
        $specPaths = $this->discoverSpecs();

        foreach ($specPaths as $specPath) {
            $loader      = SpecLoader::fromFile($specPath);
            $nsSuffix    = NameMapper::namespaceFromSpec($specPath);
            $namespace   = $this->rootNamespace . '\\' . $nsSuffix;
            $relSpecPath = $this->relativeSpecPath($specPath);

            foreach ($loader->schemas() as $schemaName => $schema) {
                $body = EnumCompiler::compile($schemaName, $schema)
                    ?? SchemaCompiler::compile($schemaName, $schema, $namespace);

                if ($body === null) {
                    continue;
                }

                $className = NameMapper::className($schemaName);
                $relPath   = "{$nsSuffix}/{$className}.php";
                $files[$relPath] = Emitter::emit($body, $namespace, $relSpecPath, $loader->hash);
            }
        }

        ksort($files);
        return $files;
    }

    /**
     * Write the result of {@see self::generate()} to disk under {@see self::$outputRoot}.
     *
     * Returns the list of relative paths written.
     *
     * @return list<string>
     */
    public function writeTo(string $outputRoot): array
    {
        $files = $this->generate();
        $written = [];

        // Clean the output root (we own the directory completely).
        if (is_dir($outputRoot)) {
            self::rmrf($outputRoot);
        }

        foreach ($files as $rel => $contents) {
            $abs = $outputRoot . DIRECTORY_SEPARATOR . $rel;
            $dir = dirname($abs);
            if (!is_dir($dir) && !mkdir($dir, 0o755, true) && !is_dir($dir)) {
                throw new \RuntimeException("Cannot create directory: {$dir}");
            }
            if (file_put_contents($abs, $contents) === false) {
                throw new \RuntimeException("Cannot write: {$abs}");
            }
            $written[] = $rel;
        }

        return $written;
    }

    /**
     * @return list<string>
     */
    private function discoverSpecs(): array
    {
        if (!is_dir($this->specsDir)) {
            throw new \RuntimeException("Specs directory not found: {$this->specsDir}");
        }

        $files = [];
        foreach (scandir($this->specsDir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (!preg_match('/\.ya?ml$/i', $entry)) {
                continue;
            }
            $files[] = $this->specsDir . DIRECTORY_SEPARATOR . $entry;
        }
        sort($files);
        return $files;
    }

    private function relativeSpecPath(string $absPath): string
    {
        $repoRoot = dirname($this->specsDir);
        if (str_starts_with($absPath, $repoRoot . DIRECTORY_SEPARATOR)) {
            return substr($absPath, strlen($repoRoot) + 1);
        }
        return $absPath;
    }

    private static function rmrf(string $path): void
    {
        if (is_file($path) || is_link($path)) {
            @unlink($path);
            return;
        }
        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            self::rmrf($path . DIRECTORY_SEPARATOR . $entry);
        }
        @rmdir($path);
    }
}
