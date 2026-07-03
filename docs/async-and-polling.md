---
title: Emissão assíncrona e polling no SDK PHP da NFE.io
sidebar_label: Assíncrono e polling
sidebar_position: 3
slug: emissao-assincrona-e-polling
description: Entenda o contrato HTTP 202, os resultados Pending e Issued discriminados por union types nativos com instanceof, e como montar um loop de polling com FlowStatus::isTerminal().
---

# Emissão assíncrona e polling

A emissão de documentos fiscais é, em geral, **assíncrona**: a API aceita a
requisição (HTTP 202) e segue processando. Esta página explica o contrato de
retorno, os dois tipos de resultado e como acompanhar o processamento até um
estado terminal.

## O contrato HTTP 202

Quando a API responde **202 Accepted**, o documento ainda **não** foi
materializado — ela apenas aceitou o pedido. Quando responde **201/200**, o
documento já está pronto. O `create()` traduz isso em um **union type nativo do
PHP**: um de dois tipos.

| Resposta HTTP | Tipo de resultado | Significado |
| --- | --- | --- |
| 202 Accepted | `*Pending` | Em processamento; reconsulte depois. |
| 201 / 200 | `*Issued` | Documento já materializado. |

:::note `*Pending` e `*Issued` por recurso
Cada recurso expõe seu par de resultados — por exemplo,
`Nfe\Response\ServiceInvoicePending` e `Nfe\Response\ServiceInvoiceIssued` (o
mesmo vale para `ProductInvoice*` e `ConsumerInvoice*`). Todos implementam as
interfaces marcadoras `Nfe\Response\Pending` e `Nfe\Response\Issued`, que é
contra o que você testa com `instanceof`.
:::

## Os dois tipos de resultado

### `*Pending` (202)

- `invoiceId()` — id extraído do último segmento do header `Location`; use-o
  para reconsultar enquanto processa.
- `location()` — o valor bruto do header `Location`.

:::warning `Pending` não tem corpo de nota
Um HTTP 202 não traz o documento — o objeto `Pending` carrega **apenas**
`invoiceId()` e `location()`. Não é possível ler valores de imposto ou número da
nota dele; busque com `retrieve()` depois que o processamento chegar a um estado
terminal. Um 202 **sem** header `Location` lança
`Nfe\Exception\InvalidRequestException`.
:::

### `*Issued` (201/200)

- `resource()` — o documento já materializado (DTO hidratado).

## Distinguindo o resultado

Não há campo `status` nem métodos `pending?()`/`issued?()` — use `instanceof`:

```php
use Nfe\Response\Pending;

$result = $nfe->serviceInvoices->create($companyId, [
    'cityServiceCode' => '2690',
    'servicesAmount'  => 100.0,
    'description'     => 'Suporte',
    'borrower'        => ['federalTaxNumber' => 191, 'name' => 'Banco do Brasil SA'],
]);

if ($result instanceof Pending) {
    $invoiceId = $result->invoiceId();   // reconsulte com este id
} else {
    $invoice = $result->resource();      // NFS-e já pronta
}
```

## Loop de polling manual

Para NFS-e, faça polling com `getStatus()` (endpoint leve de status) até um
**estado terminal**, decidido por `Nfe\Util\FlowStatus::isTerminal()`:

```php
use Nfe\Response\Pending;
use Nfe\Util\FlowStatus;

if ($result instanceof Pending) {
    $invoiceId = $result->invoiceId();
    $deadline  = time() + 300;           // defina seu próprio orçamento de tempo
    $delay     = 1;

    do {
        sleep($delay);
        $delay = min($delay * 2, 10);    // backoff próprio
        $flow  = $nfe->serviceInvoices->getStatus($companyId, $invoiceId)['flowStatus'] ?? '';
    } while (!FlowStatus::isTerminal($flow) && time() < $deadline);

    if ($flow === 'Issued') {
        $invoice = $nfe->serviceInvoices->retrieve($companyId, $invoiceId);
    } else {
        // 'IssueFailed' | 'Cancelled' | 'CancelFailed' — ou estourou o prazo
    }
}
```

### Estados de fluxo (`flowStatus`)

Estados **terminais** (encerram o polling — `FlowStatus::TERMINAL`):

| Status | Significado |
| --- | --- |
| `Issued` | Emitido com sucesso. |
| `IssueFailed` | Falha na emissão. |
| `Cancelled` | Cancelado. |
| `CancelFailed` | Falha no cancelamento. |

Estados **não terminais** (continue o polling): `WaitingSend`, `WaitingReturn`,
`WaitingDownload`, `WaitingCalculateTaxes`, `WaitingDefineRpsNumber`,
`WaitingSendCancel`, `PullFromCityHall`.

:::warning Terminal ≠ sucesso
`FlowStatus::isTerminal()` retorna `true` para **sucesso e falha**. Sempre
compare o status concreto (`=== 'Issued'`) antes de considerar a nota emitida.
:::

:::note `getStatus()` só existe em `serviceInvoices`
Para `productInvoices` e `consumerInvoices` não há `getStatus()` — faça polling
com `retrieve()` e leia o campo de fluxo do DTO. Mas a emissão de NF-e/NFC-e é
**orientada a webhook**: prefira [Webhooks](./webhooks.md) a polling nesses
recursos.
:::

## Por que não existe `createAndWait`?

A `v3.0` **não** implementa `createAndWait()`, `pollUntilComplete()` nem
qualquer helper de espera. O contrato discriminado `*Pending`/`*Issued` somado a
`FlowStatus::isTerminal()` é suficiente para escrever loops de polling manuais,
e esses auxiliares ficam deliberadamente adiados para uma versão futura — sem
quebrar o contrato público.

:::warning Não chame helpers inexistentes
Referenciar `$nfe->serviceInvoices->createAndWait(...)` na `v3.0` gera um erro
fatal (`Call to undefined method`), pois o método não está definido.
:::

## Próximos passos

- [Tratamento de erros](./errors.md) — trate falhas durante o polling.
- [Webhooks](./webhooks.md) — receba a conclusão por push em vez de polling.
- [Primeiros passos](./getting-started.md) — a primeira emissão de ponta a ponta.
