<?php

declare(strict_types=1);

use Nfe\Build\SchemaCompiler;

it('compiles an object with required + optional fields', function () {
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

it('promotes non-required fields to nullable with null default', function () {
    $body = SchemaCompiler::compile('Foo', [
        'type'       => 'object',
        'properties' => [
            'bar' => ['type' => 'string'],
        ],
    ], 'Nfe\Generated\Test');

    expect($body)->toContain('public ?string $bar = null');
});

it('resolves $ref to a class name in the same namespace', function () {
    $body = SchemaCompiler::compile('Invoice', [
        'type'       => 'object',
        'required'   => ['borrower'],
        'properties' => [
            'borrower' => ['$ref' => '#/components/schemas/Borrower'],
        ],
    ], 'Nfe\Generated\Test');

    expect($body)->toContain('public Borrower $borrower');
});

it('emits arrays with @param list<T> docblocks', function () {
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

it('returns null when the schema is an enum (handled by EnumCompiler)', function () {
    $body = SchemaCompiler::compile('FlowStatus', [
        'type' => 'string',
        'enum' => ['A', 'B'],
    ], 'Nfe\Generated\Test');

    expect($body)->toBeNull();
});

it('sanitises reserved property names', function () {
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

it('ranks required properties before optionals', function () {
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
