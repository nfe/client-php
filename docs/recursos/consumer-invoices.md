---
title: Notas fiscais de consumidor (NFC-e)
sidebar_label: Notas de consumidor
sidebar_position: 3
slug: notas-fiscais-de-consumidor
description: Emita NFC-e modelo 65 com $nfe->consumerInvoices no host api.nfse.io /v2 — retorno discriminado 202, listagem paginada, cancelamento e inutilização de faixa.
---

# Notas fiscais de consumidor (NFC-e)

`$nfe->consumerInvoices` cobre a NFC-e (modelo 65) no host `api.nfse.io`, sob
`/v2`: emissão, listagem, consulta, cancelamento, inutilização de faixa e
downloads.

A emissão segue o **contrato 202 discriminado** (assíncrona; conclusão via
webhook): `create()` / `createWithStateTax()` devolvem
`ConsumerInvoicePending|ConsumerInvoiceIssued`.

:::tip Prefira webhooks
Como na NF-e, não há `getStatus()` — a conclusão da emissão é orientada a
webhook. Se precisar de polling, use `retrieve()` e leia o campo de fluxo do
DTO.
:::

## Métodos

| Método | Descrição | Retorno |
|---|---|---|
| `create($companyId, $data)` | Emite a NFC-e. | `ConsumerInvoicePending\|ConsumerInvoiceIssued` |
| `createWithStateTax($companyId, $stateTaxId, $data)` | Emite vinculada a uma inscrição estadual. | `ConsumerInvoicePending\|ConsumerInvoiceIssued` |
| `list($companyId, $options = [])` | Lista paginada. | `ListResponse` |
| `retrieve($companyId, $invoiceId)` | Consulta por id. | `ConsumerInvoice` |
| `cancel($companyId, $invoiceId)` | Cancela e devolve o modelo atualizado. | `ConsumerInvoice` |
| `listItems($companyId, $invoiceId)` | Lista os itens da nota. | `array` (arrays crus) |
| `listEvents($companyId, $invoiceId)` | Lista os eventos fiscais. | `array` (arrays crus) |
| `downloadPdf($companyId, $invoiceId)` | DANFE NFC-e (PDF). | `string` (bytes crus) |
| `downloadXml($companyId, $invoiceId)` | XML autorizado. | `string` |
| `downloadRejectionXml($companyId, $invoiceId)` | XML de rejeição. | `string` |
| `disableRange($companyId, $data)` | Inutiliza uma faixa de numeração. | `array` |

:::note Diferenças em relação à NF-e
NFC-e **não** tem carta de correção (CC-e), contingência EPEC nem inutilização
por nota individual — apenas `disableRange()`. A inutilização aqui usa
`POST .../disablement` (caminho e verbo diferentes do `disable` da NF-e).
:::

## Emitir uma NFC-e

```php
use Nfe\Response\Pending;

$result = $nfe->consumerInvoices->create('55df4dc6b6cd9007e4f13ee8', [
    'items' => [
        ['code' => '001', 'description' => 'Produto X', 'quantity' => 2, 'unitAmount' => 25.0],
    ],
    'payment' => [
        ['method' => 'Money', 'amount' => 50.0],
    ],
]);

if ($result instanceof Pending) {
    $result->invoiceId();   // acompanhe via webhook ou retrieve()
} else {
    $result->resource();    // Nfe\ConsumerInvoice
}
```

## Listar, consultar e cancelar

```php
$pagina = $nfe->consumerInvoices->list($companyId, [
    'pageIndex' => 1,      // 1-based
    'pageCount' => 50,
]);

$nota = $nfe->consumerInvoices->retrieve($companyId, $invoiceId);

$cancelada = $nfe->consumerInvoices->cancel($companyId, $invoiceId);
```

## Baixar arquivos

```php
file_put_contents('nfce.pdf', $nfe->consumerInvoices->downloadPdf($companyId, $invoiceId));
file_put_contents('nfce.xml', $nfe->consumerInvoices->downloadXml($companyId, $invoiceId));
```

## Inutilizar uma faixa de numeração

```php
$nfe->consumerInvoices->disableRange($companyId, [
    'environment' => 'Production',
    'serie'       => 1,
    'state'       => 'SP',
    'beginNumber' => 200,
    'lastNumber'  => 210,
    'reason'      => 'Falha de impressão',
]);
```

## Próximos passos

- [Consulta de cupons (CFe-SAT/NFC-e)](./consumer-invoice-query.md) — consulta por chave de acesso.
- [Notas de produto (NF-e)](./product-invoices.md) — modelo 55.
- [Webhooks](../webhooks.md) — conclusão por push.
