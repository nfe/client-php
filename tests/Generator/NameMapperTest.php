<?php

declare(strict_types=1);

use Nfe\Build\NameMapper;

it('maps a spec filename to a PascalCase namespace suffix', function () {
    expect(NameMapper::namespaceFromSpec('service-invoice-rtc-v1.yaml'))->toBe('ServiceInvoiceRtcV1');
    expect(NameMapper::namespaceFromSpec('consulta-cnpj-v3.yaml'))->toBe('ConsultaCnpjV3');
    expect(NameMapper::namespaceFromSpec('calculo-impostos-v1.yaml'))->toBe('CalculoImpostosV1');
    expect(NameMapper::namespaceFromSpec('product-invoice-rtc-v1.yml'))->toBe('ProductInvoiceRtcV1');
});

it('handles paths and missing extensions', function () {
    expect(NameMapper::namespaceFromSpec('/abs/path/service-invoice-rtc-v1.yaml'))->toBe('ServiceInvoiceRtcV1');
    expect(NameMapper::namespaceFromSpec('consumer-invoice-v3'))->toBe('ConsumerInvoiceV3');
});

it('produces a safe PHP class name', function () {
    expect(NameMapper::className('Borrower'))->toBe('Borrower');
    expect(NameMapper::className('Foo-Bar'))->toBe('Foo_Bar');
    expect(NameMapper::className('123Class'))->toBe('_123Class');
});
