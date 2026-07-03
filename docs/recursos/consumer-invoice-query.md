---
title: Consulta de cupons fiscais (consumerInvoiceQuery)
sidebar_label: Consulta de NFC-e/CFe
sidebar_position: 17
slug: consulta-nfce
description: Consulte cupons fiscais CFe-SAT e NFC-e pela chave de acesso de 44 dígitos e baixe o XML com $nfe->consumerInvoiceQuery no host nfe.api.nfe.io.
---

# Consulta de cupons fiscais (`consumerInvoiceQuery`)

`$nfe->consumerInvoiceQuery` consulta **cupons fiscais** — CFe-SAT e NFC-e —
pela **chave de acesso de 44 dígitos**. Recurso **global**, servido por
`nfe.api.nfe.io`.

:::note Família de dados — usa `dataApiKey`
Como [`productInvoiceQuery`](./product-invoice-query.md), pertence à família
`nfe-query`: usa a `dataApiKey` com fallback para `apiKey`. Veja
[Configuração](../configuration.md).
:::

## Métodos

| Método | Descrição | Retorno |
|---|---|---|
| `retrieve($accessKey)` | Consulta o cupom pela chave de acesso. | `TaxCoupon` |
| `downloadXml($accessKey)` | XML do cupom. | `string` (bytes crus) |

## Consultar um cupom

```php
$accessKey = '35260159596908000152590008719000024150288589'; // 44 dígitos

$cupom = $nfe->consumerInvoiceQuery->retrieve($accessKey);
```

:::warning Chave de acesso é validada localmente
A chave precisa ter **44 dígitos numéricos**; um valor fora do formato lança
`Nfe\Exception\InvalidRequestException` antes de qualquer requisição.
:::

## Baixar o XML

```php
file_put_contents('cupom.xml', $nfe->consumerInvoiceQuery->downloadXml($accessKey));
```

:::note Sem PDF
Diferente da consulta de NF-e, a consulta de cupons expõe apenas o XML — não há
`downloadPdf()`.
:::

## Veja também

- [Consulta de NF-e](./product-invoice-query.md) — o equivalente para NF-e modelo 55.
- [Notas de consumidor (NFC-e)](./consumer-invoices.md) — emissão de NFC-e.
- [Configuração](../configuration.md) — modelo de duas chaves.
