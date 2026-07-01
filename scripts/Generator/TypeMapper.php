<?php

declare(strict_types=1);

namespace Nfe\Build;

/**
 * Translates an OpenAPI 3.x schema fragment into a PHP type hint.
 *
 * Conservative by design: when a schema is ambiguous (heterogeneous oneOf,
 * deeply nested anyOf, missing type, etc.), we fall back to `mixed`. The
 * generator emits a docblock @var alongside whenever it can be more
 * specific than the runtime type allows.
 */
final class TypeMapper
{
    /**
     * @param array<string, mixed> $schema   Property schema fragment.
     * @param string               $namespace Namespace where the parent class lives (used to resolve $ref).
     * @return array{php: string, doc: string|null, nullable: bool}
     */
    public static function map(array $schema, string $namespace): array
    {
        $nullable = ($schema['nullable'] ?? false) === true;

        if (isset($schema['$ref']) && is_string($schema['$ref'])) {
            $ref = self::resolveRef($schema['$ref']);
            return [
                'php'      => ($nullable ? '?' : '') . $ref,
                'doc'      => null,
                'nullable' => $nullable,
            ];
        }

        if (isset($schema['oneOf']) && is_array($schema['oneOf'])) {
            return self::mapUnion($schema['oneOf'], $namespace, $nullable);
        }

        $type = $schema['type'] ?? null;

        if ($type === 'array') {
            $itemDoc = isset($schema['items']) && is_array($schema['items'])
                ? self::map($schema['items'], $namespace)['php']
                : 'mixed';
            // Strip leading ? from item type for the doc; arrays themselves can be nullable.
            $itemDoc = ltrim($itemDoc, '?');
            return [
                'php'      => ($nullable ? '?' : '') . 'array',
                'doc'      => 'list<' . $itemDoc . '>',
                'nullable' => $nullable,
            ];
        }

        $primitive = match ($type) {
            'string'  => 'string',
            'integer' => 'int',
            'number'  => 'float',
            'boolean' => 'bool',
            'object'  => 'array', // free-form objects: keep as array
            default   => null,
        };

        if ($primitive === null) {
            return [
                'php'      => 'mixed',
                'doc'      => null,
                'nullable' => $nullable,
            ];
        }

        return [
            'php'      => ($nullable ? '?' : '') . $primitive,
            'doc'      => $type === 'object' ? 'array<string, mixed>' : null,
            'nullable' => $nullable,
        ];
    }

    /**
     * @param list<array<string, mixed>> $alternatives
     * @return array{php: string, doc: string|null, nullable: bool}
     */
    private static function mapUnion(array $alternatives, string $namespace, bool $nullable): array
    {
        $types = [];
        $hasObject = false;

        foreach ($alternatives as $alt) {
            if (!is_array($alt)) {
                continue;
            }
            $mapped = self::map($alt, $namespace);
            $bare = ltrim($mapped['php'], '?');

            // If one alternative is a complex type, we cannot express the union safely in PHP type system.
            if ($bare === 'mixed' || $bare === 'array' || str_contains($bare, '\\')) {
                $hasObject = true;
            }
            $types[$bare] = true;
        }

        if ($hasObject || count($types) > 2) {
            return [
                'php'      => 'mixed',
                'doc'      => implode('|', array_keys($types)),
                'nullable' => $nullable,
            ];
        }

        $php = implode('|', array_keys($types));
        return [
            'php'      => ($nullable ? '?' : '') . $php,
            'doc'      => null,
            'nullable' => $nullable,
        ];
    }

    /**
     * "#/components/schemas/Borrower" -> "Borrower"
     */
    private static function resolveRef(string $ref): string
    {
        $pos = strrpos($ref, '/');
        return $pos === false ? $ref : substr($ref, $pos + 1);
    }
}
