---
title: Pessoas jurídicas (legalPeople) no SDK PHP da NFE.io
sidebar_label: Pessoas jurídicas
sidebar_position: 7
slug: pessoas-juridicas
description: CRUD de pessoas jurídicas (tomadores PJ) vinculadas a uma empresa com $nfe->legalPeople — create, createBatch sequencial, findByTaxNumber e delete.
---

# Pessoas jurídicas (`legalPeople`)

`$nfe->legalPeople` gerencia o cadastro de **pessoas jurídicas** (tomadores PJ)
vinculadas a uma empresa emissora. Escopo de **empresa** (todo método recebe
`$companyId` como primeiro argumento), família `main` (`api.nfe.io` `/v1`),
chave principal.

## Métodos

| Método | Descrição | Retorno |
|---|---|---|
| `list($companyId)` | Lista as pessoas jurídicas da empresa. | `ListResponse` |
| `create($companyId, $data)` | Cadastra uma PJ. | `LegalPerson` |
| `retrieve($companyId, $personId)` | Consulta por id. | `LegalPerson` |
| `update($companyId, $personId, $data)` | Atualiza. | `LegalPerson` |
| `delete($companyId, $personId)` | Exclui. | `void` |
| `createBatch($companyId, $batch)` | Cadastra várias em sequência. | `array` |
| `findByTaxNumber($companyId, $taxNumber)` | Busca por CNPJ (client-side). | `?LegalPerson` |

:::note Exclusão aqui é `delete(): void`
Diferente de `companies->remove()` (que retorna `{deleted, id}`), pessoas usam
`delete()` e não retornam corpo.
:::

## Exemplos

### Cadastrar uma pessoa jurídica

```php
$pessoa = $nfe->legalPeople->create('55df4dc6b6cd9007e4f13ee8', [
    'federalTaxNumber' => 11444555000149,   // int para PJ
    'name'             => 'Cliente Exemplo LTDA',
    'email'            => 'contato@exemplo.com.br',
    'address'          => [
        'postalCode' => '01310-100',
        'street'     => 'Avenida Paulista',
        'number'     => '1000',
        'city'       => ['code' => '3550308', 'name' => 'São Paulo'],
        'state'      => 'SP',
    ],
]);
```

:::warning `federalTaxNumber` é `int` para PJ
No DTO `LegalPerson`, `federalTaxNumber` é `?int` (em `NaturalPerson` é
`?string`). O SDK não valida nem coage no create — envie o tipo correto.
:::

### Lote sequencial

```php
$resultado = $nfe->legalPeople->createBatch($companyId, [
    ['federalTaxNumber' => 11444555000149, 'name' => 'Empresa A'],
    ['federalTaxNumber' => 61365284000104, 'name' => 'Empresa B'],
]);
```

:::note `createBatch` é sequencial
As criações são feitas **uma a uma** (sem paralelismo) — para listas grandes,
espere tempo proporcional ao tamanho do lote.
:::

### Buscar, atualizar e excluir

```php
$pj = $nfe->legalPeople->findByTaxNumber($companyId, 11444555000149);

if ($pj !== null) {
    $nfe->legalPeople->update($companyId, $pj->id, ['email' => 'novo@exemplo.com.br']);
    $nfe->legalPeople->delete($companyId, $pj->id);   // void
}
```

## Veja também

- [Pessoas físicas](./natural-people.md) — o equivalente PF.
- [Consulta de CNPJ](./legal-entity-lookup.md) — enriqueça o cadastro com dados da Receita.
- [Notas de serviço](./service-invoices.md) — use a PJ como `borrower`.
