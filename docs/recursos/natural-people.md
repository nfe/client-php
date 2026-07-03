---
title: Pessoas físicas (naturalPeople) no SDK PHP da NFE.io
sidebar_label: Pessoas físicas
sidebar_position: 8
slug: pessoas-fisicas
description: CRUD de pessoas físicas (tomadores PF) vinculadas a uma empresa com $nfe->naturalPeople — federalTaxNumber como string, createBatch sequencial e findByTaxNumber.
---

# Pessoas físicas (`naturalPeople`)

`$nfe->naturalPeople` gerencia o cadastro de **pessoas físicas** (tomadores PF)
vinculadas a uma empresa emissora. Escopo de **empresa**, família `main`
(`api.nfe.io` `/v1`), chave principal. A API é idêntica à de
[pessoas jurídicas](./legal-people.md), com uma diferença de tipo importante.

## Métodos

| Método | Descrição | Retorno |
|---|---|---|
| `list($companyId)` | Lista as pessoas físicas da empresa. | `ListResponse` |
| `create($companyId, $data)` | Cadastra uma PF. | `NaturalPerson` |
| `retrieve($companyId, $personId)` | Consulta por id. | `NaturalPerson` |
| `update($companyId, $personId, $data)` | Atualiza. | `NaturalPerson` |
| `delete($companyId, $personId)` | Exclui. | `void` |
| `createBatch($companyId, $batch)` | Cadastra várias em sequência. | `array` |
| `findByTaxNumber($companyId, $taxNumber)` | Busca por CPF (client-side). | `?NaturalPerson` |

## Exemplos

### Cadastrar uma pessoa física

```php
$pessoa = $nfe->naturalPeople->create('55df4dc6b6cd9007e4f13ee8', [
    'federalTaxNumber' => '31119298000',   // string para PF
    'name'             => 'Maria da Silva',
    'email'            => 'maria@example.com',
]);
```

:::warning `federalTaxNumber` é `string` para PF
No DTO `NaturalPerson`, `federalTaxNumber` é `?string` — diferente de
`LegalPerson`/`Company`, onde é `?int`. O SDK não valida nem coage no create;
enviar o tipo errado pode causar rejeição ou perda de zeros à esquerda do CPF.
:::

### Lote, busca e exclusão

```php
$nfe->naturalPeople->createBatch($companyId, [
    ['federalTaxNumber' => '31119298000', 'name' => 'Maria da Silva'],
    ['federalTaxNumber' => '19100000000', 'name' => 'João Souza'],
]); // sequencial, sem paralelismo

$pf = $nfe->naturalPeople->findByTaxNumber($companyId, '31119298000');

if ($pf !== null) {
    $nfe->naturalPeople->delete($companyId, $pf->id);   // void
}
```

## Veja também

- [Pessoas jurídicas](./legal-people.md) — o equivalente PJ.
- [Consulta de CPF](./natural-person-lookup.md) — valide a situação cadastral na Receita.
- [Notas de serviço](./service-invoices.md) — use a PF como `borrower`.
