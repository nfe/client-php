---
title: Verificação de webhooks da NFE.io em PHP
sidebar_label: Webhooks
sidebar_position: 5
slug: webhooks
description: Verifique a assinatura HMAC-SHA1 das entregas de webhook da NFE.io com a classe estática Nfe\Webhook, leia o corpo cru antes de parsear o JSON e torne seus handlers idempotentes.
---

# Webhooks

Em vez de fazer polling até um estado terminal, você pode receber a conclusão da
emissão por push. A NFE.io entrega um webhook; seu endpoint **verifica a
assinatura** e reage ao evento. Este guia cobre a verificação canônica via
`Nfe\Webhook`.

## Como a NFE.io assina as entregas

Cada entrega traz o cabeçalho `X-Hub-Signature` com um HMAC-SHA1 calculado sobre
os **bytes exatos** do corpo da requisição, no formato `sha1=<40 hex>`:

```text
X-Hub-Signature: sha1=BCD17C02B9E3B40A18E745E7E04247E4AD2DD935
```

## A API de verificação

A verificação vive na classe **estática** `Nfe\Webhook` — ela não precisa de um
`Nfe\Client`, não lê configuração e não faz nenhuma chamada de rede.

:::warning `Nfe\Webhook` ≠ `$nfe->webhooks`
`$nfe->webhooks` é o **CRUD** de webhooks (criar/listar/atualizar alvos de
entrega — veja o [cookbook](./recursos/webhooks.md)). A verificação de
assinatura é a classe estática `Nfe\Webhook`; não procure
`$nfe->webhooks->verifySignature()` — não existe.
:::

```php
use Nfe\Webhook;

$ok = Webhook::verifySignature(
    payload: file_get_contents('php://input'),
    signature: $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '',
    secret: $_ENV['NFE_WEBHOOK_SECRET'],
);
```

### `verifySignature(): bool`

Retorna `true` **somente** quando o HMAC-SHA1 confere exatamente. A comparação é
feita em tempo constante (`hash_equals()`), o prefixo `sha1=` é opcional (hex
puro também é aceito) e a comparação de algoritmo/hex é case-insensitive. Um
quarto argumento `$algo` (padrão `'sha1'`) permite outro algoritmo — um header
`sha256=` contra `$algo = 'sha1'` é **rejeitado** (sem downgrade).

:::tip Nunca lança exceção
`verifySignature()` **nunca** lança. Entrada ausente, malformada, com algoritmo
errado ou conteúdo não hexadecimal resulta em `false`.
:::

### `constructEvent(): Nfe\WebhookEvent`

Quando você quer não só validar, mas também desempacotar o evento, use
`constructEvent()`. Ele verifica primeiro; em caso de falha de assinatura **ou**
de JSON inválido, lança `Nfe\Exception\SignatureVerificationException`.

```php
use Nfe\Webhook;

$event = Webhook::constructEvent(
    payload: $raw,
    sigHeader: $signature,
    secret: $_ENV['NFE_WEBHOOK_SECRET'],
);

$event->type;       // ex.: "service_invoice.issued_successfully" (a chave `action` do envelope v2)
$event->data;       // array com o payload (a chave `payload` do envelope)
$event->id;         // id estável para deduplicação, ou null
$event->createdAt;  // timestamp ISO-8601 da entrega, ou null
```

`Nfe\WebhookEvent` é um objeto de valor imutável (`final readonly`). Se o corpo
carrega o envelope v2 `{ action, payload }`, ele é desembrulhado: `type` recebe
`action` e `data` recebe o `payload` interno. Sem envelope, o helper procura um
campo `type`/`event_type`/`action` no nível raiz.

## Leia o corpo CRU antes de parsear o JSON

A NFE.io assina os bytes que entregou. Leia o corpo cru **antes** de qualquer
parsing de JSON e passe esses bytes ao verificador.

:::warning Não re-serialize o payload
Nunca passe um array já parseado e re-serializado (por exemplo
`json_encode($payload)`). A ordem das chaves e os espaços em branco vão diferir
dos bytes assinados, e a verificação falhará de forma imprevisível. Sempre use o
corpo cru — `file_get_contents('php://input')` (ou `$request->getContent()` no
Laravel/Symfony).
:::

## Exemplo: endpoint em PHP puro

```php
<?php

use Nfe\Exception\SignatureVerificationException;
use Nfe\Webhook;

require __DIR__ . '/vendor/autoload.php';

$raw       = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '';

try {
    $event = Webhook::constructEvent($raw, $signature, $_ENV['NFE_WEBHOOK_SECRET']);
} catch (SignatureVerificationException) {
    http_response_code(401);
    exit;
}

// Dedupe pelo id do evento/nota antes de aplicar efeitos colaterais.
processarEventoIdempotente($event->type, $event->data, $event->id);

http_response_code(200);
```

:::note No Laravel
Use `$request->getContent()` como corpo cru e
`$request->header('X-Hub-Signature')` como assinatura, e **exclua a rota do
middleware de CSRF** (webhooks não têm token CSRF).
:::

## Validade não é frescor — handlers devem ser idempotentes

A NFE.io **não** envia primitiva de anti-replay: as entregas trazem apenas o
HMAC-SHA1 sobre o corpo, sem timestamp e sem nonce. Uma assinatura válida prova
**autenticidade**, mas não **frescor** — uma entrega reproduzida (replay)
carrega uma assinatura perfeitamente válida.

:::warning Sempre dedupe
Trate seus handlers como **idempotentes** e faça **deduplicação pelo
`$event->id`** (id do evento ou da nota). Processar a mesma entrega duas vezes
não pode gerar efeitos colaterais duplicados.
:::

## Próximos passos

- [Cookbook de webhooks](./recursos/webhooks.md) — CRUD dos alvos de entrega.
- [Emissão assíncrona e polling](./async-and-polling.md) — polling como alternativa ao push.
- [Tratamento de erros](./errors.md) — `SignatureVerificationException` na hierarquia.
