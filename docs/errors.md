---
title: Tratamento de erros no SDK PHP da NFE.io
sidebar_label: Tratamento de erros
sidebar_position: 4
slug: tratamento-de-erros
description: A hierarquia Nfe\Exception, a tabela de códigos HTTP por classe, padrões idiomáticos de catch com instanceof, validação client-side fail-fast e erros de rede.
---

# Tratamento de erros

Todos os erros de API do SDK derivam de `Nfe\Exception\ApiErrorException` (que
estende `RuntimeException`), então você pode capturar a família inteira com um
único `catch`. Esta página descreve a hierarquia, os códigos HTTP mapeados,
padrões de `catch` por tipo, a validação client-side e os erros de rede.

## A hierarquia de erros

Todas as classes vivem em `Nfe\Exception\`:

| Classe | Código HTTP | Quando ocorre |
| --- | --- | --- |
| `ApiErrorException` | — | Base de toda a família. |
| `AuthenticationException` | 401 | Chave de API ausente ou inválida. |
| `AuthorizationException` | 403 | Chave válida, mas sem permissão/plano para o recurso. |
| `InvalidRequestException` | 400 / 422 | Requisição malformada, reprovada na validação — ou validação **local** (síncrona, antes do HTTP). |
| `NotFoundException` | 404 | Recurso não existe. |
| `RateLimitException` | 429 | Excesso de requisições. |
| `ServerException` | 5xx | Falha no servidor da API. |
| `ApiConnectionException` | — | Falha de rede/cURL (DNS, conexão recusada, TLS, timeout). |
| `SignatureVerificationException` | — | Assinatura de webhook reprovada. |

:::note Não há type guards — use `instanceof`
O SDK não expõe funções `isXxx()`. Capture as subclasses específicas primeiro e
`ApiErrorException` como rede de segurança.
:::

## Contexto carregado pelos erros HTTP

Erros derivados de uma resposta HTTP carregam contexto público para
diagnóstico: `->statusCode`, `->responseBody`, `->responseHeaders` e
`->errorCode`, além do `getMessage()` usual.

```php
use Nfe\Exception\ApiErrorException;

try {
    $nfe->serviceInvoices->retrieve($companyId, $invoiceId);
} catch (ApiErrorException $e) {
    $e->statusCode;      // ex.: 404
    $e->errorCode;       // código de erro legível por máquina, quando presente
    $e->getMessage();    // mensagem de diagnóstico
}
```

:::warning Cuidado ao logar `responseBody`/`responseHeaders`
O corpo e os headers brutos podem conter dados sensíveis (PII do payload,
detalhes da conta). Prefira logar `statusCode` + `errorCode` + `getMessage()` e
reserve o corpo bruto para depuração pontual.
:::

## Padrões de `catch` por tipo

Capture do mais específico para o mais genérico:

```php
use Nfe\Exception\ApiConnectionException;
use Nfe\Exception\ApiErrorException;
use Nfe\Exception\AuthenticationException;
use Nfe\Exception\AuthorizationException;
use Nfe\Exception\InvalidRequestException;
use Nfe\Exception\RateLimitException;
use Nfe\Exception\ServerException;

try {
    $result = $nfe->serviceInvoices->create($companyId, $payload);
} catch (AuthenticationException $e) {
    // 401 — revise a chave de API.
    throw $e;
} catch (AuthorizationException $e) {
    // 403 — a chave não tem permissão/plano para este recurso.
    throw $e;
} catch (InvalidRequestException $e) {
    // 400/422 ou validação local — corrija o payload.
    error_log("Requisição inválida: {$e->getMessage()}");
} catch (RateLimitException $e) {
    // 429 — aguarde antes de tentar de novo (o SDK já retentou com backoff).
    throw $e;
} catch (ServerException $e) {
    // 5xx — falha do lado do servidor; reportar/retentar com backoff.
    throw $e;
} catch (ApiConnectionException $e) {
    // Falha de rede (DNS, conexão recusada, TLS, timeout).
    throw $e;
} catch (ApiErrorException $e) {
    // Rede de segurança para qualquer erro do SDK.
    error_log("[{$e->errorCode}] HTTP {$e->statusCode}: {$e->getMessage()}");
    throw $e;
}
```

## Validação client-side (fail-fast)

Validações client-side (id de empresa vazio, chave de acesso com tamanho errado,
CEP/UF inválidos, texto de carta de correção vazio) lançam
`InvalidRequestException` **antes** de qualquer requisição HTTP:

```php
use Nfe\Exception\InvalidRequestException;

try {
    $nfe->serviceInvoices->retrieve('', 'abc');
} catch (InvalidRequestException $e) {
    // Lançada de forma síncrona, sem rede. $e->statusCode é null aqui.
    error_log($e->getMessage());
}
```

:::note Sem `statusCode` na validação local
Por não vir de uma resposta HTTP, um `InvalidRequestException` client-side tem
`statusCode` nulo — diferente do mesmo erro vindo de um 400/422.
:::

## Retentativas automáticas

Antes de qualquer exceção chegar ao seu código, o transporte já retentou
**HTTP 429** e **5xx** conforme a `Nfe\Http\RetryPolicy` configurada (padrão: 3
retentativas com backoff exponencial e jitter). Um `RateLimitException` ou
`ServerException` capturado significa que o orçamento de retentativas se
esgotou. Erros 4xx de negócio não são retentados. Veja
[Configuração](./configuration.md).

## Erros de rede

Quando nenhuma troca HTTP se completa, o SDK lança `ApiConnectionException` em
vez de devolver uma resposta — DNS, conexão recusada, falha de TLS, timeout de
conexão ou leitura.

```php
use Nfe\Exception\ApiConnectionException;

try {
    $nfe->companies->list();
} catch (ApiConnectionException $e) {
    error_log("Falha de conexão: {$e->getMessage()}");
}
```

## Próximos passos

- [Emissão assíncrona e polling](./async-and-polling.md) — trate falhas no loop.
- [Configuração](./configuration.md) — `timeout` e `RetryPolicy`.
- [Webhooks](./webhooks.md) — `SignatureVerificationException` na prática.
