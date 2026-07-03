---
title: Cálculo de impostos (taxCalculation) no SDK PHP da NFE.io
sidebar_label: Cálculo de impostos
sidebar_position: 13
slug: calculo-de-impostos
description: Calcule os impostos de uma operação com o motor tributário da NFE.io via $nfe->taxCalculation->calculate, com escopo de tenant e payload em array.
---

# Cálculo de impostos (`taxCalculation`)

`$nfe->taxCalculation` aciona o **motor tributário** da NFE.io para calcular os
impostos de uma operação. Escopo de **tenant** (recebe `$tenantId` como primeiro
argumento), host `api.nfse.io` (`/v2`).

:::note Usa a `apiKey` principal
Apesar de ser um serviço de consulta, o cálculo de impostos **não** é família de
dados — sempre usa a chave principal.
:::

## Métodos

| Método | Descrição | Retorno |
|---|---|---|
| `calculate($tenantId, $request)` | Calcula os impostos da operação. | `array` (resposta crua) |

Requisição e resposta são arrays associativos sem DTO. Como referência do shape
esperado, o SDK embarca a classe gerada
`Nfe\Generated\CalculoImpostosV1\CalculateRequest` — você pode espelhá-la, mas
não é um tipo obrigatório.

## Calcular os impostos de uma operação

```php
$resultado = $nfe->taxCalculation->calculate($tenantId, [
    'operationType' => 'Sale',
    'issuer'        => ['federalTaxNumber' => 11444555000149, 'state' => 'SP'],
    'recipient'     => ['federalTaxNumber' => 61365284000104, 'state' => 'RJ'],
    'items'         => [
        [
            'code'       => '001',
            'ncm'        => '85171231',
            'quantity'   => 1,
            'unitAmount' => 1999.90,
        ],
    ],
]);

// A resposta é um array cru com os tributos calculados por item.
```

:::warning Validação local
`$tenantId` vazio ou `items` vazio lançam
`Nfe\Exception\InvalidRequestException` **antes** de qualquer requisição.
:::

:::tip Combine com os códigos fiscais
Os valores aceitos em campos de classificação (código de operação, finalidade de
aquisição, perfis de contribuinte) vêm de [`taxCodes`](./tax-codes.md).
:::

## Veja também

- [Códigos fiscais](./tax-codes.md) — dados de referência do motor.
- [Notas de produto (NF-e)](./product-invoices.md) — emissão com os valores calculados.
- [Tratamento de erros](../errors.md).
