---
title: Roteamento multi-host no SDK PHP da NFE.io
sidebar_label: Roteamento multi-host
sidebar_position: 8
slug: roteamento-multi-host
description: Entenda como o SDK roteia cada recurso para o host correto (api.nfe.io, api.nfse.io e os hosts de dados), o modelo de duas chaves e o override por requisição com RequestOptions.
---

# Roteamento multi-host

A plataforma NFE.io expõe várias APIs em hosts diferentes. O SDK roteia cada
recurso **automaticamente** para o host certo — nenhum recurso hard-codeia uma
URL. Cada recurso declara uma **família** de API e obtém seu host e sua chave a
partir do `Nfe\Config`.

## Famílias, hosts e recursos

| Família | Host | Recursos |
| --- | --- | --- |
| `main` | `api.nfe.io` (`/v1`) | `serviceInvoices`, `companies`, `legalPeople`, `naturalPeople`, `webhooks` |
| `cte` | `api.nfse.io` (`/v2`) | `productInvoices`, `consumerInvoices`, `transportationInvoices`, `inboundProductInvoices`, `taxCalculation`, `taxCodes`, `stateTaxes` |
| `addresses` | `address.api.nfe.io/v2` | consulta de endereços (CEP) |
| `legal-entity` | `legalentity.api.nfe.io` | consulta de pessoa jurídica (CNPJ) |
| `natural-person` | `naturalperson.api.nfe.io` | consulta de pessoa física (CPF) |
| `nfe-query` | `nfe.api.nfe.io` | `productInvoiceQuery`, `consumerInvoiceQuery` |

:::note O segmento de versão
Para a família `main`, o `/v1` é fornecido pela versão de API de cada recurso,
não pelo host. O host de `addresses` já embute o `/v2`. Uma família desconhecida
cai no host `main` como padrão seguro.
:::

## Modelo de duas chaves

O SDK usa duas chaves de API. As **famílias de dados** preferem `dataApiKey`
(com fallback para `apiKey`); todas as outras famílias usam `apiKey`.

As famílias de dados são:

- `addresses` (CEP)
- `legal-entity` (CNPJ)
- `natural-person` (CPF)
- `nfe-query` (consultas de NF-e/NFC-e por chave de acesso)

```php
$nfe = new Nfe\Client(
    apiKey: $_ENV['NFE_API_KEY'],
    dataApiKey: $_ENV['NFE_DATA_API_KEY'] ?? null,
);
```

:::warning `cte` NÃO é família de dados
A família `cte` (`api.nfse.io` — emissão de NF-e/NFC-e, CT-e, entrada, cálculo
de impostos, tax codes e inscrições estaduais) usa a **`apiKey` principal**, e
não a `dataApiKey`. Neste SDK a emissão é uma capacidade central, não uma
consulta de dados. Código Node portado que dependia de `dataApiKey` para
`api.nfse.io` passa a enviar silenciosamente a chave principal.
:::

:::tip 403 nas famílias de dados
Um `AuthorizationException` (403) em `addresses`/`legalEntityLookup`/
`naturalPersonLookup`/`productInvoiceQuery` com uma chave principal válida
normalmente significa que o plano dessa chave não inclui o produto de dados.
Informe uma `dataApiKey` provisionada para o plano de dados.
:::

## Override por requisição: `RequestOptions->baseUrl`

Para apontar **uma chamada** para outro host (mock, proxy, ambiente de testes
próprio), use o `Nfe\Http\RequestOptions` que todo método aceita como último
argumento:

```php
use Nfe\Http\RequestOptions;

$invoice = $nfe->serviceInvoices->retrieve(
    $companyId,
    $invoiceId,
    new RequestOptions(baseUrl: 'https://mock.local/nfe'),
);
```

Para redirecionar **todo** o tráfego (testes de integração, gravação de
cassetes), injete um `Nfe\Http\Transport` customizado no `Nfe\Config` — o SDK
não tem um mapa global de overrides por família.

## Próximos passos

- [Configuração](./configuration.md) — duas chaves, `RequestOptions` e transportes.
- [Downloads](./downloads.md) — o hop para o CDN sem `Authorization`.
- [Paginação](./pagination.md) — formas de paginação por família.
