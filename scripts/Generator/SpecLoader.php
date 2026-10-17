<?php

declare(strict_types=1);

namespace Nfe\Build;

use RuntimeException;
use Symfony\Component\Yaml\Exception\ParseException;
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
     * @param int $reindentedLines Continuation lines {@see YamlNormalizer} had to re-indent (0 = parsed as-is).
     */
    public function __construct(
        public readonly array $raw,
        public readonly string $path,
        public readonly string $hash,
        public readonly int $reindentedLines = 0,
    ) {}

    public static function fromFile(string $path): self
    {
        if (!is_file($path)) {
            throw new RuntimeException("Spec file not found: {$path}");
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException("Cannot read spec file: {$path}");
        }

        [$parsed, $reindented] = self::parse($contents, $path);
        if (!is_array($parsed)) {
            throw new RuntimeException("Spec is not a YAML document: {$path}");
        }

        return new self(
            raw: $parsed,
            path: $path,
            // Hash of the bytes on disk, not of the normalised text: the header
            // marker must identify the vendored spec file itself.
            hash: hash('sha256', $contents),
            reindentedLines: $reindented,
        );
    }

    /**
     * Parse as-is; only if symfony/yaml rejects the document, retry once with
     * under-indented quoted-scalar continuation lines re-indented. A document
     * symfony/yaml already accepts is never rewritten.
     *
     * @return array{0: mixed, 1: int} Parsed value and number of re-indented lines.
     */
    private static function parse(string $contents, string $path): array
    {
        try {
            return [Yaml::parse($contents), 0];
        } catch (ParseException $original) {
            [$normalised, $reindented] = YamlNormalizer::reindentQuotedContinuations($contents);
            if ($reindented === 0) {
                throw new RuntimeException("Cannot parse spec {$path}: {$original->getMessage()}", 0, $original);
            }

            try {
                return [Yaml::parse($normalised), $reindented];
            } catch (ParseException $retry) {
                throw new RuntimeException(
                    "Cannot parse spec {$path}: {$original->getMessage()} "
                    . "(still failing after re-indenting {$reindented} quoted-scalar continuation line(s): {$retry->getMessage()})",
                    0,
                    $original,
                );
            }
        }
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
