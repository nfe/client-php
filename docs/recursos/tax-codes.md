---
title: Códigos fiscais (taxCodes) no SDK PHP da NFE.io
sidebar_label: Códigos fiscais
sidebar_position: 14
slug: codigos-fiscais
description: Liste os dados de referência do motor tributário — códigos de operação, finalidades de aquisição e perfis fiscais — com $nfe->taxCodes, paginação 1-based com máximo de 50 por página.
---

# Códigos fiscais (`taxCodes`)

`$nfe->taxCodes` expõe os **dados de referência** do motor tributário: códigos
de operação, finalidades de aquisição e perfis fiscais de emitente e
destinatário. Recurso **global** (não recebe `$companyId`), host `api.nfse.io`
(`/v2`), chave principal.

## Métodos

| Método | Descrição | Retorno |
|---|---|---|
| `listOperationCodes($opts = [])` | Códigos de operação. | `TaxCodePaginatedResponse` |
| `listAcquisitionPurposes($opts = [])` | Finalidades de aquisição. | `TaxCodePaginatedResponse` |
| `listIssuerTaxProfiles($opts = [])` | Perfis fiscais de emitente. | `TaxCodePaginatedResponse` |
| `listRecipientTaxProfiles($opts = [])` | Perfis fiscais de destinatário. | `TaxCodePaginatedResponse` |

As opções de paginação são `pageIndex` (**1-based**, padrão 1) e `pageCount`
(padrão 50, **máximo 50**).

:::note Tipo de resposta próprio
Estes métodos retornam `TaxCodePaginatedResponse` — com `items`, `currentPage`,
`totalPages` e `totalCount` — em vez do `ListResponse` dos demais recursos.
:::

## Listar códigos de operação

```php
$pagina = $nfe->taxCodes->listOperationCodes(['pageIndex' => 1, 'pageCount' => 50]);

foreach ($pagina->items as $codigo) {
    // dados de referência de cada código de operação
}

// Percorrer todas as páginas:
for ($i = 1; $i <= $pagina->totalPages; $i++) {
    $p = $nfe->taxCodes->listOperationCodes(['pageIndex' => $i, 'pageCount' => 50]);
    // ... $p->items
}
```

## Demais catálogos

```php
$nfe->taxCodes->listAcquisitionPurposes();
$nfe->taxCodes->listIssuerTaxProfiles();
$nfe->taxCodes->listRecipientTaxProfiles();
```

:::tip Cacheie localmente
São catálogos de referência que mudam raramente — cacheie o resultado na sua
aplicação em vez de consultar a cada operação.
:::

## Veja também

- [Cálculo de impostos](./tax-calculation.md) — onde esses códigos são usados.
- [Paginação](../pagination.md) — as formas de paginação do SDK.
