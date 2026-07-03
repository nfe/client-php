---
title: Notas de entrada (NF-e recebidas / DF-e)
sidebar_label: Notas de entrada
sidebar_position: 5
slug: notas-de-entrada
description: Ative a busca automática de NF-e emitidas contra o seu CNPJ (distribuição DF-e), consulte documentos e eventos, baixe XML/PDF e manifeste-se com $nfe->inboundProductInvoices.
---

# Notas de entrada (NF-e recebidas)

`$nfe->inboundProductInvoices` cobre a **distribuição DF-e**: NF-e emitidas por
terceiros contra o seu CNPJ. Vive no host `api.nfse.io`, sob `/v2`, e permite
ativar a busca automática, consultar documentos e eventos por chave de acesso,
baixar XML/PDF/JSON e registrar a **manifestação do destinatário**.

:::note Usa a `apiKey` principal
Apesar de ser um recurso de consulta, a distribuição DF-e **não** é família de
dados — sempre usa a chave principal.
:::

## Métodos

| Método | Descrição | Retorno |
|---|---|---|
| `enableAutoFetch($companyId, $data)` | Ativa a busca automática de documentos. | `InboundSettings` |
| `disableAutoFetch($companyId)` | Desativa a busca automática. | `InboundSettings` |
| `getSettings($companyId)` | Configurações atuais. | `InboundSettings` |
| `getDetails($companyId, $accessKey)` | Detalhes do documento distribuído. | `array` |
| `getProductInvoiceDetails($companyId, $accessKey)` | Detalhes da NF-e completa. | `array` |
| `getEventDetails($companyId, $accessKey, $eventKey)` | Detalhes de um evento distribuído. | `array` |
| `getProductInvoiceEventDetails($companyId, $accessKey, $eventKey)` | Detalhes de um evento da NF-e. | `array` |
| `getXml($companyId, $accessKey)` | XML do documento. | `string` (bytes crus) |
| `getEventXml($companyId, $accessKey, $eventKey)` | XML de um evento. | `string` |
| `getPdf($companyId, $accessKey)` | DANFE em PDF. | `string` |
| `getJson($companyId, $accessKey)` | Representação JSON do documento. | `array` |
| `manifest($companyId, $accessKey, $manifestType, $data = [])` | Manifestação do destinatário. | `array` |
| `reprocessWebhook($companyId, $accessKey)` | Reenvia o webhook do documento. | `array` |

## Ativar a busca automática

```php
$settings = $nfe->inboundProductInvoices->enableAutoFetch('55df4dc6b6cd9007e4f13ee8', [
    'startFromDate' => '2026-01-01',
]);
```

Depois de ativada, os documentos chegam por [webhook](../webhooks.md) conforme a
SEFAZ os distribui.

## Consultar um documento recebido

```php
$accessKey = '35260111222333000181550010000012341000012349';

$detalhes = $nfe->inboundProductInvoices->getDetails($companyId, $accessKey);
$nfeCompleta = $nfe->inboundProductInvoices->getProductInvoiceDetails($companyId, $accessKey);

file_put_contents('entrada.xml', $nfe->inboundProductInvoices->getXml($companyId, $accessKey));
file_put_contents('entrada.pdf', $nfe->inboundProductInvoices->getPdf($companyId, $accessKey));
```

## Manifestação do destinatário

```php
// Ex.: confirmar a operação
$nfe->inboundProductInvoices->manifest($companyId, $accessKey, 'Confirmation');

// Ex.: desconhecer a operação, com justificativa no corpo
$nfe->inboundProductInvoices->manifest($companyId, $accessKey, 'Ignorance', [
    'reason' => 'Operação não reconhecida',
]);
```

## Reprocessar o webhook

Se a sua aplicação perdeu uma entrega, peça o reenvio do evento do documento:

```php
$nfe->inboundProductInvoices->reprocessWebhook($companyId, $accessKey);
```

## Próximos passos

- [CT-e (entrada)](./transportation-invoices.md) — o equivalente para conhecimentos de transporte.
- [Webhooks](../webhooks.md) — verificação de assinatura das entregas.
- [Consulta de NF-e](./product-invoice-query.md) — consulta pontual por chave de acesso.
