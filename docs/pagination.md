---
title: Paginação de listas no SDK PHP da NFE.io
sidebar_label: Paginação
sidebar_position: 6
slug: paginacao
description: Itere listas com Nfe\Util\ListResponse e leia os metadados de página em Nfe\Util\ListPage — paginação por página (pageIndex 1-based) ou por cursor, conforme o recurso.
---

# Paginação

Os métodos `list()` retornam um `Nfe\Util\ListResponse` com os itens hidratados
em `->data` e os metadados de paginação em `->page`. **Não há iterador
auto-paginante** — para avançar, chame `list()` de novo com a próxima página ou
o próximo cursor.

## `Nfe\Util\ListResponse`

Tem dois membros públicos:

- `->data` — o array de DTOs hidratados (`list<T>`).
- `->page` — um `Nfe\Util\ListPage` com os metadados de paginação.

```php
$page = $nfe->serviceInvoices->list($companyId, ['pageIndex' => 1, 'pageCount' => 50]);

foreach ($page->data as $invoice) {
    echo $invoice->id, PHP_EOL;
}

$ids = array_map(fn($i) => $i->id, $page->data);
```

## `Nfe\Util\ListPage`

Os endpoints da NFE.io paginam em **uma de duas formas**, e cada recurso
preenche apenas a metade relevante (a outra fica `null`):

- **Por página** — `->pageIndex` / `->pageCount`.
- **Por cursor** — `->startingAfter` / `->endingBefore`.

`->total` é opcional e pode aparecer em qualquer uma das formas.

:::warning `pageIndex` é 1-based
O primeiro índice de página é **1**, não 0 — em `serviceInvoices`,
`consumerInvoices`, `companies` e `taxCodes`. Pedir `pageIndex => 0` não retorna
a primeira página.
:::

## Qual recurso usa qual forma

| Recurso | Forma de paginação |
| --- | --- |
| `serviceInvoices->list()` | por página (`pageIndex` 1-based / `pageCount`) |
| `consumerInvoices->list()` | por página |
| `companies->list()` | por página (+ `listAll()` client-side) |
| `taxCodes->list*()` | por página (`pageCount` máx. 50; resposta própria) |
| `productInvoices->list()` | por cursor (`startingAfter` / `limit`) — `environment` obrigatório |
| `stateTaxes->list()` | por cursor |

### Por página: `serviceInvoices`

```php
$page = $nfe->serviceInvoices->list($companyId, [
    'pageIndex' => 1,
    'pageCount' => 50,
]);

// Próxima página:
$page2 = $nfe->serviceInvoices->list($companyId, [
    'pageIndex' => 2,
    'pageCount' => 50,
]);
```

`serviceInvoices` também aceita filtros de data como `issuedBegin` e
`issuedEnd` nas mesmas opções.

### Por cursor: `productInvoices`

:::warning `environment` é obrigatório
Em `productInvoices->list()`, o argumento `$options` é **obrigatório** (não tem
default) e precisa conter `environment` — uma string `"Production"` ou
`"Test"` (o ambiente da SEFAZ).
:::

```php
$page = $nfe->productInvoices->list($companyId, [
    'environment' => 'Production',
    'limit'       => 50,
]);

// Avance usando o cursor da página anterior:
$next = $nfe->productInvoices->list($companyId, [
    'environment'   => 'Production',
    'startingAfter' => $page->page->startingAfter,
    'limit'         => 50,
]);
```

### Auto-paginação client-side: `companies->listAll()`

`companies` oferece um helper que percorre todas as páginas por você e devolve
um array simples:

```php
$todas = $nfe->companies->listAll(); // array<Company>
```

:::note `taxCodes` retorna um tipo próprio
Os métodos de `taxCodes` retornam `TaxCodePaginatedResponse` (com
`currentPage`, `totalPages`, `totalCount` e `items`) em vez de `ListResponse` —
veja o [cookbook de códigos fiscais](./recursos/tax-codes.md).
:::

## Próximos passos

- [Downloads](./downloads.md) — baixe os arquivos das notas listadas.
- [Webhooks](./webhooks.md) — receba eventos por push em vez de listar.
- [Roteamento multi-host](./multi-host-routing.md) — em qual host cada recurso vive.
