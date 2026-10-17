<?php

declare(strict_types=1);

namespace Nfe\Build;

use RuntimeException;

/**
 * Compiles an OpenAPI schema fragment carrying `enum: [...]` into a native
 * PHP 8.1+ enum source string.
 *
 * Backing type follows the schema's declared `type`:
 *   - string -> backed by string
 *   - integer -> backed by int
 *   - anything else -> backed by string (fallback)
 */
final class EnumCompiler
{
    /**
     * @param array<string, mixed> $schema
     * @return string|null The generated PHP source (without `<?php`), or null if
     *                     the schema does not carry an `enum` array.
     */
    public static function compile(string $schemaName, array $schema): ?string
    {
        if (!isset($schema['enum']) || !is_array($schema['enum']) || $schema['enum'] === []) {
            return null;
        }

        $className = NameMapper::className($schemaName);
        $backing   = self::backingType($schema['type'] ?? 'string');
        $cases     = [];
        $byName    = [];
        $byLiteral = [];

        foreach ($schema['enum'] as $value) {
            if (!is_scalar($value)) {
                continue;
            }
            $caseName = self::caseName((string) $value);
            $literal  = $backing === 'int'
                ? (string) (int) $value
                : "'" . str_replace("'", "\\'", (string) $value) . "'";

            // Two cases with the same name or the same backing value are a
            // fatal error in PHP: refuse to emit the enum instead of shipping it.
            if (isset($byName[$caseName])) {
                throw new RuntimeException(sprintf(
                    'Enum %s: values %s and %s both map to case name %s.',
                    $schemaName,
                    var_export($byName[$caseName], true),
                    var_export($value, true),
                    $caseName,
                ));
            }
            if (isset($byLiteral[$literal])) {
                throw new RuntimeException(sprintf(
                    'Enum %s: values %s and %s both map to backing value %s.',
                    $schemaName,
                    var_export($byLiteral[$literal], true),
                    var_export($value, true),
                    $literal,
                ));
            }
            $byName[$caseName]   = $value;
            $byLiteral[$literal] = $value;

            $cases[] = "    case {$caseName} = {$literal};";
        }

        $description = self::cleanDescription($schema['description'] ?? null);
        $body = '';
        if ($description !== null) {
            $body .= "/**\n * {$description}\n */\n";
        }
        $body .= "enum {$className}: {$backing}\n{\n";
        $body .= implode("\n", $cases) . "\n";
        $body .= "}\n";

        return $body;
    }

    private static function backingType(mixed $type): string
    {
        return $type === 'integer' ? 'int' : 'string';
    }

    /**
     * Turn an enum value into a valid PHP case name.
     *
     * "WaitingDefineRpsNumber" -> "WaitingDefineRpsNumber"
     * "in-progress"            -> "InProgress"
     * "1"                      -> "Value_1"
     * "-1"                     -> "Value_Minus1"
     */
    private static function caseName(string $value): string
    {
        // If the value is already a valid PHP identifier starting with a letter or _, keep as-is.
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $value)) {
            return ucfirst($value);
        }

        // A leading minus sign on a number is meaning, not punctuation: without
        // it "1" and "-1" would both become Value_1 (#39).
        $negative = preg_match('/^-[0-9]/', $value) === 1;

        // Convert kebab/snake to PascalCase.
        $parts = preg_split('/[^A-Za-z0-9]+/', $value) ?: [];
        $studly = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $studly .= strtoupper($part[0]) . substr($part, 1);
        }

        if ($negative) {
            return 'Value_Minus' . $studly;
        }

        if ($studly === '' || ctype_digit($studly[0])) {
            $studly = 'Value_' . $studly;
        }

        return $studly;
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
