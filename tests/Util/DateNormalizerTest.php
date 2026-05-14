<?php

declare(strict_types=1);

use Nfe\Exception\InvalidRequestException;
use Nfe\Util\DateNormalizer;

it('passes through valid ISO date strings', function (): void {
    expect(DateNormalizer::toIsoDate('1990-01-15'))->toBe('1990-01-15');
    expect(DateNormalizer::toIsoDate('  1990-01-15  '))->toBe('1990-01-15');
});

it('converts DateTimeImmutable to YYYY-MM-DD', function (): void {
    expect(DateNormalizer::toIsoDate(new DateTimeImmutable('1990-01-15 12:34:56')))->toBe('1990-01-15');
});

it('rejects non-ISO format', function (): void {
    expect(fn() => DateNormalizer::toIsoDate('15/01/1990'))->toThrow(InvalidRequestException::class);
    expect(fn() => DateNormalizer::toIsoDate('Jan 15 1990'))->toThrow(InvalidRequestException::class);
});

it('rejects out-of-range months/days', function (): void {
    expect(fn() => DateNormalizer::toIsoDate('2026-13-01'))->toThrow(InvalidRequestException::class);
    expect(fn() => DateNormalizer::toIsoDate('2026-02-30'))->toThrow(InvalidRequestException::class);
});

it('rejects empty input', function (): void {
    expect(fn() => DateNormalizer::toIsoDate(''))->toThrow(InvalidRequestException::class);
});
