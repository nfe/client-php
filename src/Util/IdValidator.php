<?php

declare(strict_types=1);

namespace Nfe\Util;

use Nfe\Exception\InvalidRequestException;

/**
 * Fail-fast validators for the identifier strings the NFE.io API expects.
 *
 * All methods raise {@see InvalidRequestException} synchronously (no HTTP call) when input
 * is empty or malformed. Numeric identifiers (CNPJ, CPF, CEP, access keys) are also
 * normalised: punctuation, spaces, and dashes are stripped and only digits returned.
 *
 * Resources call these as the first line of each public method that takes an identifier.
 */
final class IdValidator
{
    /** UFs (27) plus Exterior (EX) and Não Aplicável (NA) — mirrors Node SDK's `BrazilianState`. */
    private const STATES = [
        'AC', 'AL', 'AM', 'AP', 'BA', 'CE', 'DF', 'ES', 'GO',
        'MA', 'MG', 'MS', 'MT', 'PA', 'PB', 'PE', 'PI', 'PR',
        'RJ', 'RN', 'RO', 'RR', 'RS', 'SC', 'SE', 'SP', 'TO',
        'EX', 'NA',
    ];

    public static function companyId(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new InvalidRequestException('Identificador da empresa (companyId) é obrigatório.');
        }
        return $trimmed;
    }

    public static function invoiceId(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new InvalidRequestException('Identificador da nota (invoiceId) é obrigatório.');
        }
        return $trimmed;
    }

    public static function stateTaxId(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new InvalidRequestException('Identificador da inscrição estadual (stateTaxId) é obrigatório.');
        }
        return $trimmed;
    }

    public static function eventKey(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new InvalidRequestException('Chave do evento (eventKey) é obrigatória.');
        }
        return $trimmed;
    }

    /**
     * Normalise a 44-digit access key (NF-e / NFC-e / CT-e).
     *
     * Accepts input with spaces, dots, dashes, or any non-digit decoration. Returns
     * exactly 44 digits, or raises.
     */
    public static function accessKey(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if (strlen($digits) !== 44) {
            throw new InvalidRequestException(
                sprintf(
                    'Chave de acesso inválida: esperado 44 dígitos, recebido %d (input: "%s").',
                    strlen($digits),
                    $value,
                ),
            );
        }
        return $digits;
    }

    /**
     * Normalise a CNPJ (14 digits). Strips punctuation; does not validate check digits.
     */
    public static function cnpj(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if (strlen($digits) !== 14) {
            throw new InvalidRequestException(
                sprintf('CNPJ inválido: esperado 14 dígitos, recebido %d.', strlen($digits)),
            );
        }
        return $digits;
    }

    /**
     * Normalise a CPF (11 digits). Strips punctuation; does not validate check digits.
     */
    public static function cpf(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if (strlen($digits) !== 11) {
            throw new InvalidRequestException(
                sprintf('CPF inválido: esperado 11 dígitos, recebido %d.', strlen($digits)),
            );
        }
        return $digits;
    }

    /**
     * Normalise a Brazilian postal code (CEP, 8 digits).
     */
    public static function cep(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if (strlen($digits) !== 8) {
            throw new InvalidRequestException(
                sprintf('CEP inválido: esperado 8 dígitos, recebido %d.', strlen($digits)),
            );
        }
        return $digits;
    }

    /**
     * Normalise a Brazilian state code. Accepts 27 UFs plus `EX` (Exterior) and `NA`
     * (Não Aplicável). Case-insensitive; returns uppercase.
     */
    public static function state(string $value): string
    {
        $upper = strtoupper(trim($value));
        if (!in_array($upper, self::STATES, true)) {
            throw new InvalidRequestException(
                sprintf(
                    'Código de estado inválido: "%s". Esperado uma das 27 UFs brasileiras, "EX" (Exterior) ou "NA" (Não Aplicável).',
                    $value,
                ),
            );
        }
        return $upper;
    }
}
