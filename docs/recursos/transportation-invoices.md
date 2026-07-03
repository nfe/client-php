---
title: Conhecimentos de transporte (CT-e)
sidebar_label: CT-e (entrada)
sidebar_position: 4
slug: conhecimentos-de-transporte
description: Habilite o recebimento de CT-e de entrada e consulte documentos e eventos por chave de acesso com $nfe->transportationInvoices no host api.nfse.io /v2.
---

# Conhecimentos de transporte (CT-e)

`$nfe->transportationInvoices` gerencia o recebimento de **CT-e de entrada**
(conhecimentos de transporte emitidos contra a sua empresa) no host
`api.nfse.io`, sob `/v2`: habilitação/desabilitação da captura, consulta de
documentos por chave de acesso e download de XMLs.

:::note Recurso de entrada, não de emissão
Este recurso **consome** CT-e emitidos por terceiros. Ele usa a `apiKey`
principal (não é família de dados).
:::

## Métodos

| Método | Descrição | Retorno |
|---|---|---|
| `enable($companyId, $data)` | Habilita o recebimento de CT-e para a empresa. | `InboundSettings` |
| `disable($companyId)` | Desabilita o recebimento. | `InboundSettings` |
| `getSettings($companyId)` | Configurações atuais de captura. | `InboundSettings` |
| `retrieve($companyId, $accessKey)` | Consulta um CT-e por chave de acesso (44 dígitos). | `array` |
| `downloadXml($companyId, $accessKey)` | XML do CT-e. | `string` (bytes crus) |
| `getEvent($companyId, $accessKey, $eventKey)` | Consulta um evento do CT-e. | `array` |
| `downloadEventXml($companyId, $accessKey, $eventKey)` | XML de um evento. | `string` |

## Habilitar o recebimento

```php
$settings = $nfe->transportationInvoices->enable('55df4dc6b6cd9007e4f13ee8', [
    'startFromDate' => '2026-01-01',
]);

// Conferir depois:
$settings = $nfe->transportationInvoices->getSettings($companyId);
```

## Consultar um CT-e e baixar o XML

```php
$accessKey = '35260111222333000181570010000012341000012349';

$cte = $nfe->transportationInvoices->retrieve($companyId, $accessKey);

file_put_contents(
    'cte.xml',
    $nfe->transportationInvoices->downloadXml($companyId, $accessKey),
);
```

:::warning Chave de acesso é validada localmente
A chave de acesso precisa ter **44 dígitos**; um valor com tamanho errado lança
`Nfe\Exception\InvalidRequestException` antes de qualquer requisição.
:::

## Eventos do CT-e

```php
$evento = $nfe->transportationInvoices->getEvent($companyId, $accessKey, $eventKey);

file_put_contents(
    'cte-evento.xml',
    $nfe->transportationInvoices->downloadEventXml($companyId, $accessKey, $eventKey),
);
```

## Desabilitar

```php
$nfe->transportationInvoices->disable($companyId);
```

## Próximos passos

- [Notas de entrada (NF-e)](./inbound-product-invoices.md) — o equivalente para NF-e recebidas.
- [Webhooks](../webhooks.md) — receba os documentos capturados por push.
- [Downloads](../downloads.md) — o contrato de bytes crus.
