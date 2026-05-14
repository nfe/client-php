<?php

declare(strict_types=1);

use Nfe\Build\EnumCompiler;

it('compiles a string-backed enum', function () {
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

it('compiles an int-backed enum', function () {
    $body = EnumCompiler::compile('Priority', [
        'type' => 'integer',
        'enum' => [1, 2, 3],
    ]);

    expect($body)->toContain('enum Priority: int');
    expect($body)->toContain('case Value_1 = 1;');
});

it('returns null when the schema is not an enum', function () {
    expect(EnumCompiler::compile('Borrower', ['type' => 'object']))->toBeNull();
});

it('PascalCases hyphenated enum values', function () {
    $body = EnumCompiler::compile('Status', [
        'type' => 'string',
        'enum' => ['in-progress', 'not-started'],
    ]);

    expect($body)->toContain("case InProgress = 'in-progress';");
    expect($body)->toContain("case NotStarted = 'not-started';");
});
