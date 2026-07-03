---
title: Notas fiscais de serviço (NFS-e)
sidebar_label: Notas de serviço
sidebar_position: 1
slug: notas-fiscais-de-servico
description: Emita, liste, consulte, cancele e baixe NFS-e com $nfe->serviceInvoices no host api.nfe.io /v1, tratando o retorno discriminado 202 com instanceof.
---

# Notas fiscais de serviço (NFS-e)

`$nfe->serviceInvoices` é o recurso canônico de emissão da plataforma. Ele fala
com o host `api.nfe.io` no caminho `/v1` e cobre todo o ciclo de vida da NFS-e:
emissão assíncrona, listagem paginada, consulta, cancelamento, envio por e-mail
e download de PDF/XML.

A emissão segue o **contrato 202 discriminado**: `create()` devolve um union
type nativo, e você acompanha o processamento com `getStatus()` até um estado
terminal. Não existe `createAndWait()` nem `createBatch()` na `v3.0`.

:::note Host e versão
Todas as URLs efetivas ficam sob `https://api.nfe.io/v1/...`. Este é o único
recurso de nota que usa o host `api.nfe.io`; os demais usam `api.nfse.io`.
:::

## Métodos

| Método | Descrição | Retorno |
|---|---|---|
| `create($companyId, $data)` | Emite a NFS-e. | `ServiceInvoicePending\|ServiceInvoiceIssued` |
| `list($companyId, $options = [])` | Lista paginada (page-style, `pageIndex` 1-based) com filtros de data. | `ListResponse` |
| `retrieve($companyId, $invoiceId)` | Consulta uma NFS-e por id. | `ServiceInvoice` |
| `cancel($companyId, $invoiceId)` | Cancela (síncrono) e devolve o modelo atualizado. | `ServiceInvoice` |
| `sendEmail($companyId, $invoiceId)` | Reenvia a nota por e-mail ao tomador. | `array` (típico: `{sent, message}`) |
| `downloadPdf($companyId, $invoiceId)` | PDF da nota. | `string` (bytes crus) |
| `downloadXml($companyId, $invoiceId)` | XML da nota. | `string` (bytes crus) |
| `getStatus($companyId, $invoiceId)` | Snapshot leve de status. | `array` (`flowStatus`, `flowMessage`, …) |

As opções de `list()` incluem `pageIndex` (**1-based**), `pageCount`,
`issuedBegin` e `issuedEnd`. Todo método aceita ainda um
`Nfe\Http\RequestOptions` opcional como último argumento.

:::warning Validação fail-fast
Todo método valida `companyId` e `invoiceId` **antes** de qualquer chamada HTTP.
Ids vazios ou em branco lançam `Nfe\Exception\InvalidRequestException` sem
tráfego de rede.
:::

## Emitir uma NFS-e e tratar o retorno discriminado

`create()` devolve `ServiceInvoicePending` (HTTP 202, enfileirada) ou
`ServiceInvoiceIssued` (HTTP 201, já materializada). Distinga com `instanceof`
contra as interfaces marcadoras `Nfe\Response\Pending` / `Nfe\Response\Issued`.

```php
use Nfe\Response\Pending;

$result = $nfe->serviceInvoices->create('55df4dc6b6cd9007e4f13ee8', [
    'cityServiceCode' => '2690',
    'description'     => 'Manutenção e suporte técnico',
    'servicesAmount'  => 100.0,
    'borrower'        => [
        'federalTaxNumber' => 191,
        'name'             => 'Banco do Brasil SA',
    ],
]);

if ($result instanceof Pending) {
    $result->invoiceId();   // id para reconsultar enquanto processa
    $result->location();    // caminho do header Location
} else {
    $result->resource();    // Nfe\ServiceInvoice já emitida
}
```

## Acompanhar até um estado terminal (polling)

Use `getStatus()` — endpoint leve que devolve `flowStatus`/`flowMessage` — com
`Nfe\Util\FlowStatus::isTerminal()`:

```php
use Nfe\Util\FlowStatus;

$companyId = '55df4dc6b6cd9007e4f13ee8';
$invoiceId = $result instanceof Pending ? $result->invoiceId() : $result->resource()->id;

do {
    sleep(3);
    $flow = $nfe->serviceInvoices->getStatus($companyId, $invoiceId)['flowStatus'] ?? '';
} while (!FlowStatus::isTerminal($flow));

if ($flow === 'Issued') {
    $invoice = $nfe->serviceInvoices->retrieve($companyId, $invoiceId);
}
```

:::warning Terminal ≠ sucesso
`isTerminal()` também é `true` para `IssueFailed`/`CancelFailed`. Compare o
status concreto. Veja [Emissão assíncrona e polling](../async-and-polling.md).
:::

## Baixar PDF e XML (bytes crus)

Os downloads retornam uma `string` com os bytes do arquivo — grave com
`file_put_contents()`:

```php
file_put_contents('nfse.pdf', $nfe->serviceInvoices->downloadPdf($companyId, $invoiceId));
file_put_contents('nfse.xml', $nfe->serviceInvoices->downloadXml($companyId, $invoiceId));
```

## Cancelar e reenviar por e-mail

```php
$cancelada = $nfe->serviceInvoices->cancel($companyId, $invoiceId);
$cancelada->flowStatus;   // "Cancelled"

$envio = $nfe->serviceInvoices->sendEmail($companyId, $invoiceId);
$envio['sent'] ?? null;   // true/false
```

## Próximos passos

- [Primeiros passos](../getting-started.md) — instalação e primeira emissão.
- [Notas de produto (NF-e)](./product-invoices.md) — host `api.nfse.io`, cursor e CC-e.
- [Webhooks](../webhooks.md) — conclusão por push em vez de polling.
