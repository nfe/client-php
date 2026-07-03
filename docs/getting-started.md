---
title: Primeiros passos com o SDK PHP da NFE.io
sidebar_label: Primeiros passos
sidebar_position: 1
slug: primeiros-passos
description: Instale o pacote nfe/nfe via Composer, crie um Nfe\Client, emita sua primeira NFS-e e acompanhe o processamento com polling.
---

# Primeiros passos

Este guia cobre a instalação, a criação do cliente e a emissão da sua primeira
nota fiscal de serviço (NFS-e), incluindo o acompanhamento do processamento.

## 1. Instale o pacote

```sh
composer require "nfe/nfe:^3.0"
```

:::note Requisitos
PHP **8.2** ou superior, com as extensões `ext-curl`, `ext-json` e
`ext-mbstring`. O SDK não tem dependências de runtime — usa o cURL nativo do
PHP.
:::

## 2. Crie um cliente

O construtor usa **argumentos nomeados**:

```php
use Nfe\Client;

$nfe = new Client(apiKey: $_ENV['NFE_API_KEY']);
```

Recursos de **dados** (CEP, CNPJ, CPF, consultas de NF-e/NFC-e) usam uma segunda
chave opcional, `dataApiKey:`, com fallback para `apiKey`. Todas as opções estão
em [Configuração](./configuration.md).

:::note O SDK não lê variáveis de ambiente sozinho
Diferente de outros SDKs da NFE.io, o cliente PHP **não** faz fallback
automático para `NFE_API_KEY`/`NFE_DATA_API_KEY` — passe as chaves
explicitamente (por exemplo via `$_ENV`, como acima).
:::

## 3. Emita uma NFS-e

Os recursos ficam em propriedades `public readonly` do cliente — acesse-os
diretamente:

```php
$result = $nfe->serviceInvoices->create('55df4dc6b6cd9007e4f13ee8', [
    'cityServiceCode' => '2690',
    'description'     => 'Manutenção e suporte técnico',
    'servicesAmount'  => 100.0,
    'borrower'        => [
        'federalTaxNumber' => 191,
        'name'             => 'Banco do Brasil SA',
    ],
]);
```

:::tip Chaves em camelCase
O corpo (`array $data`) usa as chaves em camelCase, exatamente como a API
espera.
:::

## 4. Trate o retorno discriminado

A emissão é assíncrona. `create()` devolve um **union type nativo**
(`ServiceInvoicePending|ServiceInvoiceIssued`) — distinga com `instanceof`
contra as interfaces marcadoras `Nfe\Response\Pending` e `Nfe\Response\Issued`:

```php
use Nfe\Response\Pending;

if ($result instanceof Pending) {
    $invoiceId = $result->invoiceId();  // id para reconsultar enquanto processa (HTTP 202)
    $location  = $result->location();   // valor bruto do header Location
} else {
    $invoice = $result->resource();     // NFS-e já materializada (HTTP 201)
}
```

## 5. Acompanhe até um estado terminal (polling)

Não existe `createAndWait()` na `v3.0` — faça polling com `getStatus()` até um
estado terminal, usando `Nfe\Util\FlowStatus::isTerminal()`:

```php
use Nfe\Response\Pending;
use Nfe\Util\FlowStatus;

if ($result instanceof Pending) {
    $companyId = '55df4dc6b6cd9007e4f13ee8';
    $invoiceId = $result->invoiceId();

    do {
        sleep(3); // algumas prefeituras levam minutos
        $status = $nfe->serviceInvoices->getStatus($companyId, $invoiceId);
        $flow   = $status['flowStatus'] ?? '';
    } while (!FlowStatus::isTerminal($flow));

    if ($flow === 'Issued') {
        $invoice = $nfe->serviceInvoices->retrieve($companyId, $invoiceId);
    } else {
        // 'IssueFailed' | 'Cancelled' | 'CancelFailed'
    }
}
```

:::warning Terminal não significa sucesso
`FlowStatus::isTerminal()` retorna `true` também para os estados de **falha**
(`IssueFailed`, `CancelFailed`). Sempre inspecione o status concreto antes de
considerar a nota emitida. Veja
[Emissão assíncrona e polling](./async-and-polling.md).
:::

:::warning Produção vs. teste
A separação **produção vs. teste (homologação)** é definida na configuração da
sua conta em [app.nfe.io](https://app.nfe.io) — **não** pela chave de API nem
pelo SDK. O argumento `environment:` do cliente está reservado para uso futuro.
:::

## Próximos passos

- [Configuração](./configuration.md) — todas as opções do cliente.
- [Emissão assíncrona e polling](./async-and-polling.md) — o contrato 202 em detalhe.
- [Tratamento de erros](./errors.md) — `catch` por tipo.
- [Webhooks](./webhooks.md) — receba a conclusão por push em vez de polling.
