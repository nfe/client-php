---
title: Consulta de CPF (naturalPersonLookup) no SDK PHP da NFE.io
sidebar_label: Consulta de CPF
sidebar_position: 12
slug: consulta-cpf
description: Consulte a situação cadastral de um CPF na Receita Federal com $nfe->naturalPersonLookup->getStatus, informando CPF e data de nascimento.
---

# Consulta de CPF (`naturalPersonLookup`)

`$nfe->naturalPersonLookup` consulta a **situação cadastral** de uma pessoa
física na Receita Federal. Recurso **global**, servido por
`naturalperson.api.nfe.io`.

:::note Família de dados — usa `dataApiKey`
Com fallback para `apiKey`. Veja [Configuração](../configuration.md).
:::

## Métodos

| Método | Descrição | Retorno |
|---|---|---|
| `getStatus($cpf, $birthDate)` | Situação cadastral do CPF. | `NaturalPersonStatus` |

A consulta de CPF exige **duas credenciais do titular**: o CPF e a data de
nascimento.

## Consultar a situação cadastral

```php
$status = $nfe->naturalPersonLookup->getStatus('311.192.980-00', '1990-05-20');
```

`$birthDate` aceita uma string ISO (`YYYY-MM-DD`) ou um `\DateTimeImmutable`:

```php
$status = $nfe->naturalPersonLookup->getStatus(
    '31119298000',
    new \DateTimeImmutable('1990-05-20'),
);
```

:::warning Validação local da data
Datas malformadas ou fora de faixa lançam
`Nfe\Exception\InvalidRequestException` **antes** de qualquer requisição. O CPF
também é validado quanto ao formato.
:::

:::note Dado sensível
CPF e data de nascimento são dados pessoais — trate o resultado conforme a
LGPD: não logue o payload bruto e restrinja o acesso ao propósito da consulta.
:::

## Veja também

- [Consulta de CNPJ](./legal-entity-lookup.md) — o equivalente PJ.
- [Pessoas físicas](./natural-people.md) — cadastro de tomadores PF.
- [Configuração](../configuration.md) — modelo de duas chaves.
