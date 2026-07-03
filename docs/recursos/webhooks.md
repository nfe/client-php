---
title: Webhooks (CRUD) no SDK PHP da NFE.io
sidebar_label: Webhooks (CRUD)
sidebar_position: 9
slug: webhooks
description: Crie, liste, atualize, teste e exclua alvos de entrega de webhook por empresa com $nfe->webhooks — a verificação de assinatura fica na classe estática Nfe\Webhook.
---

# Webhooks (`webhooks`)

`$nfe->webhooks` gerencia os **alvos de entrega** de webhook por empresa —
URLs que a NFE.io chama quando eventos acontecem (nota emitida, cancelada,
documento de entrada recebido…). Escopo de **empresa**, família `main`
(`api.nfe.io` `/v1`), chave principal.

:::warning CRUD aqui, verificação lá
Este recurso é só o **CRUD** dos alvos. A verificação de assinatura das
entregas (`HMAC-SHA1`, header `X-Hub-Signature`) fica na classe **estática**
`Nfe\Webhook` — veja o guia de [Webhooks](../webhooks.md).
:::

## Métodos

| Método | Descrição | Retorno |
|---|---|---|
| `list($companyId)` | Lista os webhooks da empresa. | `ListResponse` |
| `create($companyId, $data)` | Registra um novo alvo. | `Webhook` |
| `retrieve($companyId, $webhookId)` | Consulta por id. | `Webhook` |
| `update($companyId, $webhookId, $data)` | Atualiza URL/eventos/segredo. | `Webhook` |
| `delete($companyId, $webhookId)` | Exclui. | `void` |
| `test($companyId, $webhookId)` | Dispara uma entrega de teste. | `array` |
| `getAvailableEvents()` | Lista de eventos suportados (hard-coded, sem HTTP). | `array` |

## Exemplos

### Registrar um alvo de entrega

```php
$webhook = $nfe->webhooks->create('55df4dc6b6cd9007e4f13ee8', [
    'url'    => 'https://minha-app.example.com/webhooks/nfe',
    'secret' => $_ENV['NFE_WEBHOOK_SECRET'],
]);
```

Guarde o mesmo `secret` na sua aplicação — ele é o HMAC usado para assinar cada
entrega, e você o passa a `Nfe\Webhook::verifySignature()` no seu endpoint.

### Testar, atualizar e excluir

```php
// Entrega de teste para validar o endpoint de ponta a ponta:
$nfe->webhooks->test($companyId, $webhook->id);

$nfe->webhooks->update($companyId, $webhook->id, [
    'url' => 'https://minha-app.example.com/webhooks/nfe/v2',
]);

$nfe->webhooks->delete($companyId, $webhook->id);   // void
```

### Eventos disponíveis

```php
// Lista embutida no SDK — não faz chamada de rede:
$eventos = $nfe->webhooks->getAvailableEvents();
```

## Veja também

- [Guia de webhooks](../webhooks.md) — verificação de assinatura e idempotência.
- [Emissão assíncrona e polling](../async-and-polling.md) — push vs. polling.
- [Empresas](./companies.md) — o escopo dos alvos.
