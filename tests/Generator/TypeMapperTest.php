<?php

declare(strict_types=1);

use Nfe\Build\TypeMapper;

it('sanitises a dotted schema name referenced directly via $ref', function (): void {
    $mapped = TypeMapper::map(
        ['$ref' => '#/components/schemas/DFeTech.TaxPayers.Resources.CompanyResourceItem'],
        'Nfe\\Generated\\ContribuintesV2',
    );

    expect($mapped['php'])->toBe('DFeTech_TaxPayers_Resources_CompanyResourceItem');
    expect($mapped['nullable'])->toBeFalse();
});

it('sanitises a dotted schema name in a nullable $ref', function (): void {
    $mapped = TypeMapper::map(
        [
            '$ref'     => '#/components/schemas/DFeTech.TaxPayers.Resources.MunicipalTaxResourceItem',
            'nullable' => true,
        ],
        'Nfe\\Generated\\ContribuintesV2',
    );

    expect($mapped['php'])->toBe('?DFeTech_TaxPayers_Resources_MunicipalTaxResourceItem');
    expect($mapped['nullable'])->toBeTrue();
});

it('sanitises dotted schema names inside a oneOf union', function (): void {
    $mapped = TypeMapper::map(
        [
            'oneOf' => [
                ['$ref' => '#/components/schemas/DFeTech.TaxPayers.A'],
                ['$ref' => '#/components/schemas/DFeTech.TaxPayers.B'],
            ],
        ],
        'Nfe\\Generated\\ContribuintesV2',
    );

    expect($mapped['php'])->toBe('DFeTech_TaxPayers_A|DFeTech_TaxPayers_B');
});

it('sanitises dotted schema names used as array item types', function (): void {
    $mapped = TypeMapper::map(
        [
            'type'  => 'array',
            'items' => ['$ref' => '#/components/schemas/DFeTech.TaxPayers.Resources.StateTaxResourceItem'],
        ],
        'Nfe\\Generated\\ContribuintesV2',
    );

    expect($mapped['php'])->toBe('array');
    expect($mapped['doc'])->toBe('list<DFeTech_TaxPayers_Resources_StateTaxResourceItem>');
});

it('leaves already-valid ref names untouched', function (): void {
    $mapped = TypeMapper::map(['$ref' => '#/components/schemas/Borrower'], 'Nfe\\Generated\\ContribuintesV2');

    expect($mapped['php'])->toBe('Borrower');
});

it('prefixes a ref name that starts with a digit', function (): void {
    $mapped = TypeMapper::map(['$ref' => '#/components/schemas/123Thing'], 'Nfe\\Generated\\ContribuintesV2');

    expect($mapped['php'])->toBe('_123Thing');
});
