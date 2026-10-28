<?php

declare(strict_types=1);

use Nfe\Build\EnumCompiler;

it('compiles a string-backed enum', function (): void {
    $body = EnumCompiler::compile('FlowStatus', [
        'type' => 'string',
        'enum' => ['WaitingDefineRpsNumber', 'Issued', 'Cancelled'],
    ]);

    expect($body)->not->toBeNull();
    expect($body)->toContain('enum FlowStatus: string');
    expect($body)->toContain("case WaitingDefineRpsNumber = 'WaitingDefineRpsNumber';");
    expect($body)->toContain("case Issued = 'Issued';");
    expect($body)->toContain("case Cancelled = 'Cancelled';");
});

it('compiles an int-backed enum', function (): void {
    $body = EnumCompiler::compile('Priority', [
        'type' => 'integer',
        'enum' => [1, 2, 3],
    ]);

    expect($body)->toContain('enum Priority: int');
    expect($body)->toContain('case Value_1 = 1;');
});

it('names negative int values with a Minus prefix so they never collide with positives (#39)', function (): void {
    // Shape of ConsultaCteV2 DFe.NetCore.Domain.Enums.EntityStatus.
    $body = EnumCompiler::compile('DFe.NetCore.Domain.Enums.EntityStatus', [
        'type' => 'integer',
        'enum' => [0, 1, -1],
    ]);

    expect($body)->toContain('case Value_0 = 0;');
    expect($body)->toContain('case Value_1 = 1;');
    expect($body)->toContain('case Value_Minus1 = -1;');
});

it('names negative string values with a Minus prefix too', function (): void {
    $body = EnumCompiler::compile('Code', [
        'type' => 'string',
        'enum' => ['10', '-10', '-1.5'],
    ]);

    expect($body)->toContain("case Value_10 = '10';");
    expect($body)->toContain("case Value_Minus10 = '-10';");
    expect($body)->toContain("case Value_Minus15 = '-1.5';");
});

it('emits an enum with negative values that PHP can compile', function (): void {
    $body = EnumCompiler::compile('EntityStatusCompileCheck', [
        'type' => 'integer',
        'enum' => [0, 1, -1],
    ]);

    // Compiling the generator's own output (from the literal schema above) is
    // the point: the #39 enum parsed fine and only failed at compile time.
    eval("namespace Nfe\\Tests\\Generated\\Eval;\n" . $body);
    $enum = 'Nfe\\Tests\\Generated\\Eval\\EntityStatusCompileCheck';

    expect($enum::from(-1)->name)->toBe('Value_Minus1');
    expect($enum::from(1)->name)->toBe('Value_1');
});

it('fails loud when two values map to the same case name', function (): void {
    expect(fn() => EnumCompiler::compile('Status', [
        'type' => 'string',
        'enum' => ['in-progress', 'in progress'],
    ]))->toThrow(RuntimeException::class, "Enum Status: values 'in-progress' and 'in progress' both map to case name InProgress.");
});

it('fails loud when two values map to the same int backing value', function (): void {
    expect(fn() => EnumCompiler::compile('Code', [
        'type' => 'integer',
        'enum' => ['1', '01'],
    ]))->toThrow(RuntimeException::class, 'both map to backing value 1');
});

it('returns null when the schema is not an enum', function (): void {
    expect(EnumCompiler::compile('Borrower', ['type' => 'object']))->toBeNull();
});

it('PascalCases hyphenated enum values', function (): void {
    $body = EnumCompiler::compile('Status', [
        'type' => 'string',
        'enum' => ['in-progress', 'not-started'],
    ]);

    expect($body)->toContain("case InProgress = 'in-progress';");
    expect($body)->toContain("case NotStarted = 'not-started';");
});
