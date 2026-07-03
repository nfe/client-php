---
title: Webhooks (CRUD) no SDK PHP da NFE.io
sidebar_label: Webhooks (CRUD)
sidebar_position: 9
slug: webhooks
description: Crie, liste, atualize, pingue e exclua alvos de entrega de webhook da conta com $nfe->webhooks — a verificação de assinatura fica na classe estática Nfe\Webhook.
---

# Webhooks (`webhooks`)

`$nfe->webhooks` gerencia os **alvos de entrega** de webhook — URLs que a
NFE.io chama quando eventos acontecem (nota emitida, cancelada, documento de
entrada recebido…). Webhooks são registrados por **conta** (não por empresa):
`api.nfe.io` `/v2/webhooks`, chave principal. Todas as empresas da conta
disparam para os mesmos alvos; use `filters` para escolher os eventos.

:::warning CRUD aqui, verificação lá
Este recurso é só o **CRUD** dos alvos. A verificação de assinatura das
entregas (`HMAC-SHA1`, header `X-Hub-Signature`) fica na classe **estática**
`Nfe\Webhook` — veja o guia de [Webhooks](../webhooks.md).
:::

## Métodos

| Método | Descrição | Retorno |
|---|---|---|
| `listAccountWebhooks()` | Lista os webhooks da conta. | `ListResponse<AccountWebhook>` |
| `createAccountWebhook($data)` | Registra um novo alvo. | `AccountWebhook` |
| `retrieveAccountWebhook($webhookId)` | Consulta por id. | `AccountWebhook` |
| `updateAccountWebhook($webhookId, $data)` | Substitui o webhook (PUT integral — veja o aviso). | `AccountWebhook` |
| `deleteAccountWebhook($webhookId)` | Exclui um webhook. | `void` |
| `deleteAllAccountWebhooks()` | ⚠️ Exclui **TODOS** os webhooks da conta. | `void` |
| `pingAccountWebhook($webhookId)` | Dispara uma entrega de teste. | `void` |
| `fetchEventTypes()` | Lista viva de event types da API. | `list<string>` |

Os métodos por empresa (`list($companyId)`, `create($companyId, …)`, `retrieve`,
`update`, `delete`, `test`) e o `getAvailableEvents()` estão **deprecated**: a
rota `/v1/companies/{id}/webhooks` retorna 404 na API atual e os literais
`invoice.*` não existem. Migre para os equivalentes de conta acima.

## Exemplos

### Registrar um alvo de entrega

```php
$webhook = $nfe->webhooks->createAccountWebhook([
    'uri'         => 'https://minha-app.example.com/webhooks/nfe',
    'contentType' => 'json',
    'secret'      => $_ENV['NFE_WEBHOOK_SECRET'],   // 32–64 caracteres
    'filters'     => ['service_invoice.issued_successfully'],
]);
```

:::warning A URL é verificada na criação
No `create`, a NFE.io **pinga a `uri`** com um `POST` e exige resposta **2xx**.
Se o seu endpoint ainda não estiver no ar (ou exigir a assinatura para
responder 200), a criação falha.
:::

Guarde o mesmo `secret` na sua aplicação — ele é o HMAC usado para assinar cada
entrega, e você o passa a `Nfe\Webhook::verifySignature()` no seu endpoint. O
`secret` é **ecoado apenas no create**; `retrieve`/`list` o omitem.

### Atualizar — PUT é substituição integral

:::warning Sempre parta do estado atual
`updateAccountWebhook()` faz um `PUT` que **substitui o webhook inteiro**:
campos omitidos voltam ao padrão. Um update sem `status` **desativa o
webhook**. Monte o corpo a partir do `retrieve`:
:::

```php
$atual = $nfe->webhooks->retrieveAccountWebhook($webhook->id);

$nfe->webhooks->updateAccountWebhook($webhook->id, [
    'uri'         => 'https://minha-app.example.com/webhooks/nfe/v2', // a mudança
    'contentType' => $atual->contentType,
    'status'      => $atual->status,
    'filters'     => $atual->filters,
]);
```

### Testar e excluir

```php
// Entrega de teste (PUT /v2/webhooks/{id}/pings, responde 204):
$nfe->webhooks->pingAccountWebhook($webhook->id);

$nfe->webhooks->deleteAccountWebhook($webhook->id);   // void

// ⚠️ Destrutivo e sem undo — remove TODOS os alvos da conta:
$nfe->webhooks->deleteAllAccountWebhooks();
```

### Eventos disponíveis

```php
// Lista viva da API (GET /v2/webhooks/eventTypes) — 46 ids no padrão
// resource.event_action, ex.: service_invoice.issued_successfully,
// product_invoice.issued_error, consumer_invoice.cancelled_successfully…
$eventos = $nfe->webhooks->fetchEventTypes();
```

Use esses ids em `filters` ao criar/atualizar um webhook. Sem `filters`, o
alvo recebe todos os eventos.

## Veja também

- [Guia de webhooks](../webhooks.md) — verificação de assinatura e idempotência.
- [Emissão assíncrona e polling](../async-and-polling.md) — push vs. polling.
- [Empresas](./companies.md) — as empresas da conta que geram os eventos.
