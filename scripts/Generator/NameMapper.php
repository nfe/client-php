<?php

declare(strict_types=1);

namespace Nfe\Build;

/**
 * Translates OpenAPI spec filenames into PHP namespace suffixes and back.
 *
 * The mapping is deterministic and mechanical: kebab-case to PascalCase.
 * No semantic normalisation — Portuguese words stay in Portuguese (e.g.,
 * "consulta-cnpj-v3" -> "ConsultaCnpjV3").
 */
final class NameMapper
{
    /**
     * "service-invoice-rtc-v1.yaml" -> "ServiceInvoiceRtcV1"
     * "consulta-cnpj-v3"            -> "ConsultaCnpjV3"
     */
    public static function namespaceFromSpec(string $specFilename): string
    {
        $base = preg_replace('/\.ya?ml$/i', '', basename($specFilename)) ?? $specFilename;
        $parts = preg_split('/[-_]/', $base) ?: [];

        $studly = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            // Preserve "v1" -> "V1" (uppercase the leading letter).
            $studly .= strtoupper($part[0]) . substr($part, 1);
        }

        return $studly;
    }

    /**
     * Normalise a schema name (which may already be PascalCase or snake_case) to
     * a valid PHP class name. We trust OpenAPI to give us valid identifiers,
     * but strip any unexpected characters defensively.
     */
    public static function className(string $schemaName): string
    {
        return self::phpIdentifier($schemaName);
    }

    /**
     * Turn an arbitrary schema name into a valid PHP identifier.
     *
     * Single source of truth for both emitted class names and $ref-derived
     * type-hints, so a schema name and every reference to it always agree
     * (e.g., "DFeTech.TaxPayers.Resources.X" -> "DFeTech_TaxPayers_Resources_X").
     */
    public static function phpIdentifier(string $name): string
    {
        $clean = preg_replace('/[^A-Za-z0-9_]/', '_', $name) ?? $name;

        // If it starts with a digit, prefix with underscore (PHP requirement).
        if ($clean !== '' && ctype_digit($clean[0])) {
            $clean = '_' . $clean;
        }

        return $clean;
    }
}
