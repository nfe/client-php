---
title: Consulta de endereços (addresses) no SDK PHP da NFE.io
sidebar_label: Endereços
sidebar_position: 10
slug: enderecos
description: Consulta de endereços por CEP com $nfe->addresses->lookupByPostalCode no host address.api.nfe.io/v2 — família de dados, usa a dataApiKey.
---

# Consulta de endereços (`addresses`)

O recurso `$nfe->addresses` consulta endereços **por CEP**. Faz parte da família
de **dados** `addresses`, servida por `address.api.nfe.io/v2` (o host já embute
a versão).

:::note Família de dados — configure `dataApiKey`
`addresses` usa a `dataApiKey` quando presente, com **fallback** para a
`apiKey`. Configure `dataApiKey:` para separar a chave de consulta da chave de
emissão. Veja [Configuração](../configuration.md).
:::

## Métodos

| Método | Descrição | Retorno |
|---|---|---|
| `lookupByPostalCode($cep)` | Consulta um endereço por CEP. | `AddressLookupResponse` |

:::warning CEP é o único endpoint
Não existe busca por termo livre nem por filtro — `lookupByPostalCode()` é o
único método. Não procure `search()`/`lookupByTerm()`: esses endpoints não
existem mais na API.
:::

## Consultar por CEP

```php
$nfe = new Nfe\Client(
    apiKey: $_ENV['NFE_API_KEY'],
    dataApiKey: $_ENV['NFE_DATA_API_KEY'] ?? null,
);

$resposta = $nfe->addresses->lookupByPostalCode('01310-100');

$endereco = $resposta->addresses[0];
$endereco['street'];         // "Avenida Paulista"
$endereco['city'] ?? null;   // dados do município

$resposta->raw;              // payload original completo, se precisar
```

O CEP é aceito com ou sem hífen e é normalizado para 8 dígitos.

:::warning CEP é validado antes do HTTP
Um CEP que não normalize para **8 dígitos** lança
`Nfe\Exception\InvalidRequestException` **antes** de qualquer requisição. Veja
[Tratamento de erros](../errors.md).
:::

:::note O envelope `{ address }` é desembrulhado
A API responde `{ "address": { … } }`; o SDK desembrulha para
`->addresses` — uma lista de **1 elemento** — para manter o shape estável.
:::

## Veja também

- [Configuração](../configuration.md) — modelo de duas chaves e `dataApiKey`.
- [Consulta de CNPJ](./legal-entity-lookup.md) e [Consulta de CPF](./natural-person-lookup.md) — outras famílias de dados.
