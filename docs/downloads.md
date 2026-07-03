---
title: Downloads de PDF e XML no SDK PHP da NFE.io
sidebar_label: Downloads
sidebar_position: 7
slug: downloads
description: Baixe PDF/XML das notas. Todos os métodos de download do SDK PHP devolvem os bytes crus como string — salve com file_put_contents, sem contratos especiais por recurso.
---

# Downloads

Os recursos de nota expõem métodos para baixar PDF e XML. No SDK PHP o contrato
é **um só**: todo método `download*()` / `getPdf()` / `getXml()` retorna os
**bytes crus do arquivo como uma `string` PHP** — não um stream, não um objeto
de arquivo, não uma URI.

## Salvando um arquivo

```php
file_put_contents(
    'nota.pdf',
    $nfe->serviceInvoices->downloadPdf($companyId, $invoiceId),
);

file_put_contents(
    'nota.xml',
    $nfe->serviceInvoices->downloadXml($companyId, $invoiceId),
);
```

O mesmo padrão vale para todos os recursos:

```php
// NF-e (DANFE) emitida pela sua empresa:
$pdf = $nfe->productInvoices->downloadPdf($companyId, $invoiceId);

// NF-e de terceiros, por chave de acesso (44 dígitos):
$danfe = $nfe->productInvoiceQuery->downloadPdf($accessKey);

// XML de CT-e recebido:
$xml = $nfe->transportationInvoices->downloadXml($companyId, $accessKey);

// Documentos de entrada (DF-e):
$pdf = $nfe->inboundProductInvoices->getPdf($companyId, $accessKey);
```

:::tip Diferença em relação aos SDKs Node.js e Ruby
No SDK Ruby, os downloads de `product_invoices` devolvem um objeto com a URI do
arquivo; no Node, um `Buffer`. No PHP não há exceção: **todos** os downloads —
inclusive os de `productInvoices` — devolvem a `string` de bytes pronta para
gravar. Não portar esse detalhe cegamente de outro SDK.
:::

## Métodos de download por recurso

| Recurso | Métodos |
| --- | --- |
| `serviceInvoices` | `downloadPdf`, `downloadXml` |
| `productInvoices` | `downloadPdf`, `downloadXml`, `downloadRejectionXml`, `downloadEpecXml`, `downloadCorrectionLetterPdf`, `downloadCorrectionLetterXml` |
| `consumerInvoices` | `downloadPdf`, `downloadXml`, `downloadRejectionXml` |
| `transportationInvoices` | `downloadXml`, `downloadEventXml` |
| `inboundProductInvoices` | `getPdf`, `getXml`, `getEventXml` |
| `productInvoiceQuery` | `downloadPdf`, `downloadXml` |
| `consumerInvoiceQuery` | `downloadXml` |

## Como o SDK segue o redirecionamento para o CDN

Alguns endpoints de download respondem com um redirect (302/303/307) para um CDN
(por exemplo, S3). O SDK segue o redirecionamento **em uma segunda requisição
sem o header `Authorization`** — sua chave de API nunca chega ao host do CDN.
Isso é transparente: você recebe os bytes finais.

:::note Memória
Como o retorno é uma `string` em memória, arquivos muito grandes (ZIPs de
período inteiro, por exemplo) ocupam RAM proporcional ao tamanho. Para volumes
altos, baixe em lotes menores.
:::

## Próximos passos

- [Roteamento multi-host](./multi-host-routing.md) — em qual host cada download vive.
- [Paginação](./pagination.md) — liste notas antes de baixá-las.
- [Cookbook por recurso](./recursos/) — exemplos completos por recurso.
