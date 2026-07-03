---
title: Inscrições estaduais (stateTaxes) no SDK PHP da NFE.io
sidebar_label: Inscrições estaduais
sidebar_position: 15
slug: inscricoes-estaduais
description: Gerencie as inscrições estaduais (IE) da empresa emissora com $nfe->stateTaxes — pré-requisito para emitir NF-e/NFC-e, com paginação por cursor e envelope stateTax.
---

# Inscrições estaduais (`stateTaxes`)

`$nfe->stateTaxes` gerencia as **inscrições estaduais (IE)** de uma empresa
emissora — pré-requisito para emitir [NF-e](./product-invoices.md) e
[NFC-e](./consumer-invoices.md). Escopo de **empresa**, host `api.nfse.io`
(`/v2`), chave principal.

## Métodos

| Método | Descrição | Retorno |
|---|---|---|
| `list($companyId, $opts = null)` | Lista por cursor. | `ListResponse` |
| `create($companyId, $data)` | Cadastra uma IE. | `NfeStateTax` |
| `retrieve($companyId, $stateTaxId)` | Consulta por id. | `NfeStateTax` |
| `update($companyId, $stateTaxId, $data)` | Atualiza. | `NfeStateTax` |
| `delete($companyId, $stateTaxId)` | Exclui. | `void` |

:::note Envelope `{ stateTax }` no corpo
`create()` e `update()` embrulham o corpo como `{ "stateTax": { … } }`
automaticamente (paridade com o SDK Node) — você passa só o conteúdo interno; a
resposta volta hidratada sem envelope.
:::

## Cadastrar uma inscrição estadual

```php
$ie = $nfe->stateTaxes->create('55df4dc6b6cd9007e4f13ee8', [
    'code'            => 'SP',
    'taxNumber'       => '111.111.111.111',
    'serie'           => 1,
    'number'          => 1,
    'environmentType' => 'Production',
]);
```

## Listar (cursor) e consultar

```php
$pagina = $nfe->stateTaxes->list($companyId, ['limit' => 25]);

foreach ($pagina->data as $ie) {
    echo $ie->code, PHP_EOL;
}

// Próxima página:
$proxima = $nfe->stateTaxes->list($companyId, [
    'startingAfter' => $pagina->page->startingAfter,
    'limit'         => 25,
]);

$ie = $nfe->stateTaxes->retrieve($companyId, $stateTaxId);
```

## Atualizar e excluir

```php
$nfe->stateTaxes->update($companyId, $stateTaxId, ['serie' => 2]);

$nfe->stateTaxes->delete($companyId, $stateTaxId);   // void
```

:::tip Emissão vinculada à IE
Com a IE cadastrada, emita NF-e/NFC-e vinculadas a ela via
`createWithStateTax($companyId, $stateTaxId, $data)` nos recursos de
[produto](./product-invoices.md) e [consumidor](./consumer-invoices.md).
:::

## Veja também

- [Notas de produto (NF-e)](./product-invoices.md) — emissão que depende da IE.
- [Consulta de CNPJ](./legal-entity-lookup.md) — descubra a IE de terceiros por UF.
- [Paginação](../pagination.md) — cursor vs. página.
