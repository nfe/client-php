<?php

declare(strict_types=1);

namespace Nfe\Build;

/**
 * Compiles an OpenAPI object schema into a PHP `final readonly class` source string.
 *
 * We intentionally use string templating with careful escaping rather than
 * nikic/php-parser's BuilderFactory for the body. The output we care about
 * is small (constructor property promotion only) and template-based output
 * is easier to read in PRs than AST-generated output.
 */
final class SchemaCompiler
{
    /**
     * @param array<string, mixed> $schema  Object schema.
     * @return string|null The generated PHP file body (without `<?php` prefix), or null if this
     *                     schema cannot be expressed as a class (e.g. pure enum, primitive type alias).
     */
    public static function compile(string $schemaName, array $schema, string $namespace): ?string
    {
        // Schemas with `enum:` are handled by EnumCompiler, not here.
        if (isset($schema['enum'])) {
            return null;
        }

        // Only object-shaped schemas become classes. Primitive aliases are skipped — we'd
        // rather have references through TypeMapper resolve to the underlying primitive.
        $type = $schema['type'] ?? null;
        if ($type !== null && $type !== 'object') {
            return null;
        }

        $className = NameMapper::className($schemaName);
        $properties = isset($schema['properties']) && is_array($schema['properties'])
            ? $schema['properties']
            : [];
        $required = isset($schema['required']) && is_array($schema['required'])
            ? array_flip($schema['required'])
            : [];

        $description = self::cleanDescription($schema['description'] ?? null);

        $paramLines = [];
        $docLines   = [];
        $orderedProps = self::orderProperties($properties, $required);

        foreach ($orderedProps as $propName => $propSchema) {
            if (!is_string($propName) || !is_array($propSchema)) {
                continue;
            }
            $isRequired = isset($required[$propName]);
            [$paramLine, $docLine] = self::compileProperty(
                propName: $propName,
                propSchema: $propSchema,
                isRequired: $isRequired,
                namespace: $namespace,
            );
            $paramLines[] = $paramLine;
            if ($docLine !== null) {
                $docLines[] = $docLine;
            }
        }

        $paramLines = self::dropUnreachableDefaults($paramLines);

        $body  = '';
        if ($description !== null) {
            $body .= "/**\n * {$description}\n */\n";
        } elseif ($docLines !== []) {
            $body .= "/**\n";
            foreach ($docLines as $line) {
                $body .= " * {$line}\n";
            }
            $body .= " */\n";
        }

        $body .= "final readonly class {$className}\n{\n";
        if ($paramLines === []) {
            $body .= "    public function __construct() {}\n";
        } else {
            $body .= "    public function __construct(\n";
            $body .= implode(",\n", $paramLines) . ",\n";
            $body .= "    ) {}\n";
        }
        $body .= "}\n";

        return $body;
    }

    /**
     * @param array<string, mixed> $propSchema
     * @return array{0: string, 1: string|null} [paramLine, docLine]
     */
    private static function compileProperty(
        string $propName,
        array $propSchema,
        bool $isRequired,
        string $namespace,
    ): array {
        $mapped = TypeMapper::map($propSchema, $namespace);
        $phpType = $mapped['php'];

        // For non-required properties without an explicit nullable, default to ?T = null
        // so callers can omit them. This matches OpenAPI's "optional" semantics.
        $defaultsToNull = !$isRequired;
        if ($defaultsToNull && !str_starts_with($phpType, '?') && $phpType !== 'mixed') {
            $phpType = '?' . $phpType;
        }

        $default = '';
        if ($defaultsToNull || $mapped['nullable']) {
            $default = ' = null';
        }

        $safeName = self::safePropertyName($propName);
        $line = "        public {$phpType} \${$safeName}{$default}";
        $docLine = null;

        if ($mapped['doc'] !== null) {
            $docTypeWithNullable = $defaultsToNull || $mapped['nullable']
                ? $mapped['doc'] . '|null'
                : $mapped['doc'];
            $docLine = "@param {$docTypeWithNullable} \${$safeName}";
            if ($safeName !== $propName) {
                $docLine .= " (API field: {$propName})";
            }
        } elseif ($safeName !== $propName) {
            $docLine = "@param {$phpType} \${$safeName} (API field: {$propName})";
        }

        return [$line, $docLine];
    }

    /**
     * Strip `= null` from any parameter declared before a parameter without a
     * default (#36).
     *
     * A required-but-nullable property is emitted as `?T $x = null` among the
     * required ones; when a required non-nullable property follows, PHP 8.1+
     * deprecates the declaration ("Optional parameter declared before required
     * parameter is implicitly treated as a required parameter"). The default is
     * already unreachable there — omitting the argument, even by name, throws
     * ArgumentCountError and Reflection reports it as not optional — so
     * dropping it changes nothing for callers. The `?T` type stays, and the
     * parameter order is never touched (positional callers rely on it).
     *
     * @param list<string> $paramLines
     * @return list<string>
     */
    private static function dropUnreachableDefaults(array $paramLines): array
    {
        $requiredFollows = false;
        for ($i = count($paramLines) - 1; $i >= 0; $i--) {
            $hasDefault = str_ends_with($paramLines[$i], ' = null');
            if ($hasDefault && $requiredFollows) {
                $paramLines[$i] = substr($paramLines[$i], 0, -strlen(' = null'));
            } elseif (!$hasDefault) {
                $requiredFollows = true;
            }
        }

        return $paramLines;
    }

    /**
     * PHP reserved words and property names that need sanitisation.
     */
    private static function safePropertyName(string $name): string
    {
        $clean = preg_replace('/[^A-Za-z0-9_]/', '_', $name) ?? $name;
        if ($clean !== '' && ctype_digit($clean[0])) {
            $clean = '_' . $clean;
        }

        $reserved = ['class', 'function', 'const', 'list', 'array', 'string', 'int', 'float', 'bool', 'true', 'false', 'null'];
        if (in_array(strtolower($clean), $reserved, true)) {
            $clean .= '_';
        }

        return $clean;
    }

    /**
     * Order properties so that required ones come first (allowing trailing
     * optionals with defaults).
     *
     * @param array<string, mixed> $properties
     * @param array<string, int>   $required Flipped: name => index
     * @return array<string, mixed>
     */
    private static function orderProperties(array $properties, array $required): array
    {
        $req = [];
        $opt = [];
        foreach ($properties as $name => $schema) {
            if (isset($required[$name])) {
                $req[$name] = $schema;
            } else {
                $opt[$name] = $schema;
            }
        }
        return $req + $opt;
    }

    private static function cleanDescription(mixed $desc): ?string
    {
        if (!is_string($desc) || trim($desc) === '') {
            return null;
        }
        $oneLine = preg_replace('/\s+/', ' ', trim($desc)) ?? $desc;
        return strlen($oneLine) > 240 ? substr($oneLine, 0, 237) . '...' : $oneLine;
    }
}
