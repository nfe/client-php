---
title: Consulta de NF-e por chave de acesso (productInvoiceQuery)
sidebar_label: Consulta de NF-e
sidebar_position: 16
slug: consulta-nfe
description: Consulte qualquer NF-e pela chave de acesso de 44 dígitos, liste eventos e baixe DANFE/XML com $nfe->productInvoiceQuery no host nfe.api.nfe.io.
---

# Consulta de NF-e (`productInvoiceQuery`)

`$nfe->productInvoiceQuery` consulta **qualquer NF-e** (emitida por você ou por
terceiros) pela **chave de acesso de 44 dígitos**. Recurso **global** (não
recebe `$companyId`), servido por `nfe.api.nfe.io`.

:::note Consulta ≠ emissão
Este recurso é distinto de [`productInvoices`](./product-invoices.md) (emissão).
É família de **dados** (`nfe-query`): usa a `dataApiKey` com fallback para
`apiKey`. Veja [Configuração](../configuration.md).
:::

## Métodos

| Método | Descrição | Retorno |
|---|---|---|
| `retrieve($accessKey)` | Consulta a NF-e pela chave de acesso. | `ProductInvoiceDetails` |
| `downloadPdf($accessKey)` | DANFE em PDF. | `string` (bytes crus) |
| `downloadXml($accessKey)` | XML autorizado. | `string` |
| `listEvents($accessKey)` | Eventos fiscais da nota. | `array` |

## Consultar uma NF-e

```php
$accessKey = '35260111222333000181550010000012341000012349'; // 44 dígitos

$detalhes = $nfe->productInvoiceQuery->retrieve($accessKey);
```

:::warning Chave de acesso é validada localmente
A chave precisa ter **44 dígitos numéricos** — um valor fora do formato lança
`Nfe\Exception\InvalidRequestException` antes de qualquer requisição.
:::

## Baixar DANFE e XML

```php
file_put_contents('danfe.pdf', $nfe->productInvoiceQuery->downloadPdf($accessKey));
file_put_contents('nfe.xml', $nfe->productInvoiceQuery->downloadXml($accessKey));
```

## Listar os eventos fiscais

```php
$eventos = $nfe->productInvoiceQuery->listEvents($accessKey);
// cancelamentos, cartas de correção, manifestações...
```

## Veja também

- [Consulta de cupons (CFe-SAT/NFC-e)](./consumer-invoice-query.md) — o equivalente para cupons.
- [Notas de entrada](./inbound-product-invoices.md) — captura automática de NF-e contra o seu CNPJ.
- [Downloads](../downloads.md) — o contrato de bytes crus.
