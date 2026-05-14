<?php

declare(strict_types=1);

namespace Nfe\Build;

use Symfony\Component\Yaml\Yaml;

/**
 * Loads an OpenAPI YAML file and exposes the bits the generator cares about.
 *
 * No deep validation — if the file is broken, we'd rather surface that loudly
 * during generation than silently emit corrupt code.
 */
final class SpecLoader
{
    /**
     * @param array<string, mixed> $raw The full parsed OpenAPI document.
     * @param string $path The source path (used in generated header markers).
     * @param string $hash SHA-256 of the source file (used in generated header markers).
     */
    public function __construct(
        public readonly array $raw,
        public readonly string $path,
        public readonly string $hash,
    ) {}

    public static function fromFile(string $path): self
    {
        if (!is_file($path)) {
            throw new \RuntimeException("Spec file not found: {$path}");
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new \RuntimeException("Cannot read spec file: {$path}");
        }

        $parsed = Yaml::parse($contents);
        if (!is_array($parsed)) {
            throw new \RuntimeException("Spec is not a YAML document: {$path}");
        }

        return new self(
            raw: $parsed,
            path: $path,
            hash: hash('sha256', $contents),
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function schemas(): array
    {
        $schemas = $this->raw['components']['schemas'] ?? [];
        if (!is_array($schemas)) {
            return [];
        }

        $out = [];
        foreach ($schemas as $name => $schema) {
            if (is_string($name) && is_array($schema)) {
                $out[$name] = $schema;
            }
        }

        return $out;
    }
}
