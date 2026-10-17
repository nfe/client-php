<?php

declare(strict_types=1);

use Nfe\Build\SchemaCompiler;

it('compiles an object with required + optional fields', function (): void {
    $body = SchemaCompiler::compile('Borrower', [
        'type'     => 'object',
        'required' => ['name'],
        'properties' => [
            'name'  => ['type' => 'string'],
            'email' => ['type' => 'string', 'nullable' => true],
        ],
    ], 'Nfe\Generated\Test');

    expect($body)->not->toBeNull();
    expect($body)->toContain('final readonly class Borrower');
    expect($body)->toContain('public string $name');
    expect($body)->toContain('public ?string $email = null');
});

it('promotes non-required fields to nullable with null default', function (): void {
    $body = SchemaCompiler::compile('Foo', [
        'type'       => 'object',
        'properties' => [
            'bar' => ['type' => 'string'],
        ],
    ], 'Nfe\Generated\Test');

    expect($body)->toContain('public ?string $bar = null');
});

it('resolves $ref to a class name in the same namespace', function (): void {
    $body = SchemaCompiler::compile('Invoice', [
        'type'       => 'object',
        'required'   => ['borrower'],
        'properties' => [
            'borrower' => ['$ref' => '#/components/schemas/Borrower'],
        ],
    ], 'Nfe\Generated\Test');

    expect($body)->toContain('public Borrower $borrower');
});

it('emits arrays with @param list<T> docblocks', function (): void {
    $body = SchemaCompiler::compile('Container', [
        'type'       => 'object',
        'required'   => ['items'],
        'properties' => [
            'items' => [
                'type'  => 'array',
                'items' => ['type' => 'string'],
            ],
        ],
    ], 'Nfe\Generated\Test');

    expect($body)->toContain('@param list<string> $items');
    expect($body)->toContain('public array $items');
});

it('returns null when the schema is an enum (handled by EnumCompiler)', function (): void {
    $body = SchemaCompiler::compile('FlowStatus', [
        'type' => 'string',
        'enum' => ['A', 'B'],
    ], 'Nfe\Generated\Test');

    expect($body)->toBeNull();
});

it('sanitises reserved property names', function (): void {
    $body = SchemaCompiler::compile('Foo', [
        'type'       => 'object',
        'required'   => ['class'],
        'properties' => [
            'class' => ['type' => 'string'],
        ],
    ], 'Nfe\Generated\Test');

    expect($body)->toContain('public string $class_');
    expect($body)->toContain('(API field: class)');
});

it('ranks required properties before optionals', function (): void {
    $body = SchemaCompiler::compile('Foo', [
        'type'       => 'object',
        'required'   => ['name'],
        'properties' => [
            'optional' => ['type' => 'string'],
            'name'     => ['type' => 'string'],
        ],
    ], 'Nfe\Generated\Test');

    $namePos = strpos($body ?? '', '$name');
    $optionalPos = strpos($body ?? '', '$optional');

    expect($namePos)->toBeLessThan($optionalPos);
});

it('drops the unreachable default of a nullable param declared before a required one (#36)', function (): void {
    // Shape of ConsultaNfeDistribuicaoV1 Sucessonarequisio2: required-but-nullable
    // properties interleaved with required non-nullable ones.
    $body = SchemaCompiler::compile('Sucessonarequisio2', [
        'type'       => 'object',
        'required'   => ['startFromNsu', 'environmentSEFAZ', 'companyId', 'status', 'modifiedOn'],
        'properties' => [
            'startFromNsu'     => ['type' => 'string'],
            'environmentSEFAZ' => ['type' => 'string', 'nullable' => true],
            'companyId'        => ['type' => 'string'],
            'status'           => ['type' => 'string', 'nullable' => true],
            'modifiedOn'       => ['type' => 'string'],
            'tail'             => ['type' => 'string', 'nullable' => true],
            'note'             => ['type' => 'string'],
        ],
    ], 'Nfe\Generated\Test');

    // Order is unchanged; the nullable type stays; only the dead default goes.
    expect($body)->toContain(implode(",\n", [
        '        public string $startFromNsu',
        '        public ?string $environmentSEFAZ',
        '        public string $companyId',
        '        public ?string $status',
        '        public string $modifiedOn',
        '        public ?string $tail = null',
        '        public ?string $note = null',
    ]) . ",\n");
});

it('keeps the null default of a required nullable param when only optionals follow', function (): void {
    $body = SchemaCompiler::compile('Foo', [
        'type'       => 'object',
        'required'   => ['id', 'deletedOn'],
        'properties' => [
            'id'        => ['type' => 'string'],
            'deletedOn' => ['type' => 'string', 'nullable' => true],
            'extra'     => ['type' => 'string'],
        ],
    ], 'Nfe\Generated\Test');

    expect($body)->toContain('public ?string $deletedOn = null');
    expect($body)->toContain('public ?string $extra = null');
});

it('emits constructors that compile without deprecations', function (): void {
    $body = SchemaCompiler::compile('DeprecationCheck', [
        'type'       => 'object',
        'required'   => ['a', 'b'],
        'properties' => [
            'a' => ['type' => 'string', 'nullable' => true],
            'b' => ['type' => 'string'],
        ],
    ], 'Nfe\Generated\Test');

    $deprecations = [];
    set_error_handler(function (int $errno, string $message) use (&$deprecations): bool {
        $deprecations[] = $message;

        return true;
    }, E_DEPRECATED);

    try {
        // Compiling the generator's own output (from the literal schema above)
        // is the point: the #36 deprecation is raised at compile time.
        eval("namespace Nfe\\Tests\\Generated\\Eval;\n" . $body);
    } finally {
        restore_error_handler();
    }

    expect($deprecations)->toBe([]);
    $ctor = (new ReflectionClass('Nfe\\Tests\\Generated\\Eval\\DeprecationCheck'))->getConstructor();
    expect($ctor?->getNumberOfRequiredParameters())->toBe(2);
});
