<?php

declare(strict_types=1);

use Nfe\Exception\InvalidRequestException;
use Nfe\Util\IdValidator;

it('accepts non-empty companyId', function (): void {
    expect(IdValidator::companyId('abc-123'))->toBe('abc-123');
    expect(IdValidator::companyId('  abc  '))->toBe('abc');
});

it('rejects empty companyId', function (): void {
    expect(fn() => IdValidator::companyId(''))->toThrow(InvalidRequestException::class);
    expect(fn() => IdValidator::companyId('   '))->toThrow(InvalidRequestException::class);
});

it('normalises 44-digit access keys with punctuation', function (): void {
    $raw = '3526 1234 5678 9012 3456 7890 1234 5678 9012 3456 7890';
    $expected = '35261234567890123456789012345678901234567890';
    expect(IdValidator::accessKey($raw))->toBe($expected);
    expect(IdValidator::accessKey('3526.1234.5678.9012.3456.7890.1234.5678.9012.3456.7890'))->toBe($expected);
});

it('rejects access keys of wrong length', function (): void {
    expect(fn() => IdValidator::accessKey('123'))->toThrow(InvalidRequestException::class);
    expect(fn() => IdValidator::accessKey(str_repeat('1', 43)))->toThrow(InvalidRequestException::class);
    expect(fn() => IdValidator::accessKey(str_repeat('1', 45)))->toThrow(InvalidRequestException::class);
});

it('normalises CNPJ to 14 digits', function (): void {
    expect(IdValidator::cnpj('12.345.678/0001-90'))->toBe('12345678000190');
    expect(IdValidator::cnpj('12345678000190'))->toBe('12345678000190');
});

it('rejects CNPJ of wrong length', function (): void {
    expect(fn() => IdValidator::cnpj('12345678'))->toThrow(InvalidRequestException::class);
});

it('normalises CPF to 11 digits', function (): void {
    expect(IdValidator::cpf('123.456.789-01'))->toBe('12345678901');
    expect(IdValidator::cpf('12345678901'))->toBe('12345678901');
});

it('rejects CPF of wrong length', function (): void {
    expect(fn() => IdValidator::cpf('123456789'))->toThrow(InvalidRequestException::class);
});

it('normalises CEP to 8 digits', function (): void {
    expect(IdValidator::cep('01310-100'))->toBe('01310100');
    expect(IdValidator::cep('01310100'))->toBe('01310100');
});

it('rejects CEP of wrong length', function (): void {
    expect(fn() => IdValidator::cep('1310'))->toThrow(InvalidRequestException::class);
});

it('accepts the 27 Brazilian states', function (): void {
    foreach (['AC', 'AL', 'AM', 'AP', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MG', 'MS', 'MT', 'PA', 'PB', 'PE', 'PI', 'PR', 'RJ', 'RN', 'RO', 'RR', 'RS', 'SC', 'SE', 'SP', 'TO'] as $uf) {
        expect(IdValidator::state($uf))->toBe($uf);
    }
});

it('accepts EX and NA as special state codes', function (): void {
    expect(IdValidator::state('EX'))->toBe('EX');
    expect(IdValidator::state('NA'))->toBe('NA');
});

it('normalises lowercase state codes to uppercase', function (): void {
    expect(IdValidator::state('sp'))->toBe('SP');
    expect(IdValidator::state('  rj  '))->toBe('RJ');
});

it('rejects unknown state codes', function (): void {
    expect(fn() => IdValidator::state('XX'))->toThrow(InvalidRequestException::class);
    expect(fn() => IdValidator::state('ZZ'))->toThrow(InvalidRequestException::class);
    expect(fn() => IdValidator::state(''))->toThrow(InvalidRequestException::class);
});
