<?php

declare(strict_types=1);

namespace Nfe\Util;

use DateTimeImmutable;
use Nfe\Exception\InvalidRequestException;

/**
 * Normalises date inputs to the ISO-8601 `YYYY-MM-DD` form expected by the
 * NFE.io API. Accepts both string inputs (already in `YYYY-MM-DD` format)
 * and `\DateTimeImmutable` objects.
 *
 * Used by {@see \Nfe\Resource\NaturalPersonLookupResource::getStatus()} for
 * the birth date parameter.
 */
final class DateNormalizer
{
    public static function toIsoDate(string|DateTimeImmutable $input): string
    {
        if ($input instanceof DateTimeImmutable) {
            return $input->format('Y-m-d');
        }

        $trimmed = trim($input);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $trimmed)) {
            throw new InvalidRequestException(
                sprintf('Data inválida: "%s". Esperado formato YYYY-MM-DD.', $input),
            );
        }

        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $trimmed);
        if ($parsed === false || $parsed->format('Y-m-d') !== $trimmed) {
            throw new InvalidRequestException(
                sprintf('Data inválida: "%s" (mês ou dia fora do intervalo).', $input),
            );
        }

        return $trimmed;
    }
}
