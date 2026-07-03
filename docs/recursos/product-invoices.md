---
title: Notas fiscais de produto (NF-e)
sidebar_label: Notas de produto
sidebar_position: 2
slug: notas-fiscais-de-produto
description: Emita NF-e modelo 55 com $nfe->productInvoices no host api.nfse.io /v2 — listagem por cursor com environment obrigatório, carta de correção, inutilização e downloads em bytes.
---

# Notas fiscais de produto (NF-e)

`$nfe->productInvoices` cobre o ciclo de vida completo da NF-e (modelo 55) no
host `api.nfse.io`, sob `/v2`: emissão, listagem por cursor, consulta,
cancelamento, carta de correção (CC-e), inutilização e downloads.

A emissão segue o **contrato 202 discriminado** (assíncrona; conclusão via
webhook): `create()` / `createWithStateTax()` devolvem
`ProductInvoicePending|ProductInvoiceIssued`. Não há `createAndWait()` nem
`createBatch()`.

:::tip Prefira webhooks para NF-e
Diferente da NFS-e, `productInvoices` **não** tem `getStatus()`. A conclusão da
emissão é orientada a webhook — configure um alvo em
[Webhooks](../webhooks.md). Se precisar de polling, use `retrieve()` e leia o
campo de fluxo do DTO.
:::

## Métodos

| Método | Descrição | Retorno |
|---|---|---|
| `create($companyId, $data)` | Emite a NF-e. | `ProductInvoicePending\|ProductInvoiceIssued` |
| `createWithStateTax($companyId, $stateTaxId, $data)` | Emite vinculada a uma inscrição estadual. | `ProductInvoicePending\|ProductInvoiceIssued` |
| `list($companyId, $options)` | Lista por cursor; `$options` **obrigatório** com `environment`. | `ListResponse` |
| `retrieve($companyId, $invoiceId)` | Consulta por id. | `ProductInvoice` |
| `cancel($companyId, $invoiceId, $reason = null)` | Cancela; `reason` opcional vai na query. | `ProductInvoice` |
| `listItems($companyId, $invoiceId)` | Lista os itens da nota. | `array` (arrays crus) |
| `listEvents($companyId, $invoiceId)` | Lista os eventos fiscais. | `array` (arrays crus) |
| `downloadPdf($companyId, $invoiceId)` | DANFE PDF. | `string` (bytes crus) |
| `downloadXml($companyId, $invoiceId)` | XML autorizado. | `string` |
| `downloadRejectionXml($companyId, $invoiceId)` | XML de rejeição. | `string` |
| `downloadEpecXml($companyId, $invoiceId)` | XML de contingência EPEC. | `string` |
| `sendCorrectionLetter($companyId, $invoiceId, $correction)` | Emite CC-e. | `array` |
| `downloadCorrectionLetterPdf($companyId, $invoiceId)` | PDF da CC-e. | `string` |
| `downloadCorrectionLetterXml($companyId, $invoiceId)` | XML da CC-e. | `string` |
| `disable($companyId, $invoiceId, $data)` | Inutiliza uma nota específica. | `array` |
| `disableRange($companyId, $data)` | Inutiliza uma faixa de numeração. | `array` |

## Emitir uma NF-e

```php
use Nfe\Response\Pending;

$result = $nfe->productInvoices->create('55df4dc6b6cd9007e4f13ee8', [
    'buyer' => [
        'federalTaxNumber' => 11111111111111,
        'name'             => 'Cliente Exemplo LTDA',
    ],
    'items' => [
        ['code' => '001', 'description' => 'Produto X', 'quantity' => 1, 'unitAmount' => 99.9],
    ],
]);

if ($result instanceof Pending) {
    $result->invoiceId();   // acompanhe via webhook ou retrieve()
} else {
    $result->resource();    // Nfe\ProductInvoice
}
```

Para emitir vinculada a uma inscrição estadual específica (veja
[Inscrições estaduais](./state-taxes.md)):

```php
$result = $nfe->productInvoices->createWithStateTax($companyId, $stateTaxId, $data);
```

## Listar com `environment` obrigatório

A paginação é por cursor e o argumento `$options` é **obrigatório** — precisa
conter `environment` (string `"Production"` ou `"Test"`).

```php
$pagina = $nfe->productInvoices->list($companyId, [
    'environment' => 'Production',
    'limit'       => 50,
]);

foreach ($pagina->data as $nf) {
    echo $nf->id, PHP_EOL;
}

// Próxima página:
$proxima = $nfe->productInvoices->list($companyId, [
    'environment'   => 'Production',
    'startingAfter' => $pagina->page->startingAfter,
    'limit'         => 50,
]);
```

:::note `environment` é o ambiente da SEFAZ
Este `environment` (string) é um parâmetro real de ambiente da SEFAZ, separado
da configuração produção/teste da conta (definida em
[app.nfe.io](https://app.nfe.io)). O enum `Nfe\Environment` do cliente está
reservado para uso futuro.
:::

## Baixar arquivos (bytes crus)

Todos os downloads devolvem a `string` de bytes — grave com
`file_put_contents()`:

```php
file_put_contents('danfe.pdf', $nfe->productInvoices->downloadPdf($companyId, $invoiceId));
file_put_contents('nfe.xml', $nfe->productInvoices->downloadXml($companyId, $invoiceId));
```

## Carta de correção (CC-e)

Um texto vazio ou só com espaços lança
`Nfe\Exception\InvalidRequestException` antes de qualquer HTTP.

```php
$nfe->productInvoices->sendCorrectionLetter(
    $companyId,
    $invoiceId,
    'Correção do endereço de entrega do destinatário',
);

file_put_contents(
    'cce.pdf',
    $nfe->productInvoices->downloadCorrectionLetterPdf($companyId, $invoiceId),
);
```

## Cancelar e inutilizar

```php
// Cancelamento (com justificativa opcional na query):
$nfe->productInvoices->cancel($companyId, $invoiceId, 'Pedido cancelado pelo cliente');

// Inutilizar uma nota específica:
$nfe->productInvoices->disable($companyId, $invoiceId, [
    'reason' => 'Numeração não utilizada',
]);

// Inutilizar uma faixa de numeração:
$nfe->productInvoices->disableRange($companyId, [
    'environment' => 'Production',
    'serie'       => 1,
    'state'       => 'SP',
    'beginNumber' => 100,
    'lastNumber'  => 110,
    'reason'      => 'Falha de impressão',
]);
```

## Próximos passos

- [Notas de consumidor (NFC-e)](./consumer-invoices.md) — modelo 65.
- [Inscrições estaduais](./state-taxes.md) — pré-requisito para emitir NF-e.
- [Paginação](../pagination.md) — cursor vs. página.
