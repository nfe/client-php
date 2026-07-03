---
title: Configuração do cliente do SDK PHP da NFE.io
sidebar_label: Configuração
sidebar_position: 2
slug: configuracao
description: Todas as opções do Nfe\Client e do Nfe\Config — chaves de API, modelo de duas chaves, timeout, retry, logger PSR-3, transporte PSR-18 e overrides por requisição.
---

# Configuração

Esta página descreve como configurar o `Nfe\Client`: os argumentos de
conveniência do construtor, as opções avançadas que vivem em `Nfe\Config`, o
modelo de duas chaves, a política de retry e os overrides por requisição com
`Nfe\Http\RequestOptions`.

## Argumentos de `new Nfe\Client(...)`

O construtor aceita **argumentos nomeados**:

```php
use Nfe\Client;
use Nfe\Environment;

$nfe = new Client(
    apiKey: $_ENV['NFE_API_KEY'],
    dataApiKey: $_ENV['NFE_DATA_API_KEY'] ?? null,
    environment: Environment::Production,
    timeout: 60,
    userAgentSuffix: 'meu-integrador/1.0',
);
```

| Argumento | Tipo | Padrão | Descrição |
| --- | --- | --- | --- |
| `apiKey` | `?string` | `null` | Chave principal. Obrigatória quando `config` não é informado. |
| `dataApiKey` | `?string` | `null` | Chave dos serviços de dados (CEP/CNPJ/CPF/consultas). Fallback para `apiKey`. |
| `config` | `?Nfe\Config` | `null` | Quando informado, **os demais argumentos de conveniência são ignorados**. |
| `environment` | `Nfe\Environment` | `Environment::Production` | `Production` ou `Sandbox`. Reservado para uso futuro (sem efeito sobre os endpoints hoje). |
| `timeout` | `int` | `60` | Timeout por requisição, em segundos (deve ser positivo). |
| `transport` | `?Nfe\Http\Transport` | `null` | Transporte HTTP customizado (adaptador PSR-18, mock). `null` = cURL padrão. |
| `userAgentSuffix` | `?string` | `null` | Sufixo anexado ao `User-Agent` do SDK. |

:::note Validação na construção
As opções são validadas na criação. `apiKey` vazia, `dataApiKey` vazia (quando
não nula) ou `timeout` não positivo lançam `Nfe\Exception\InvalidRequestException`
antes de qualquer requisição HTTP. Veja [Tratamento de erros](./errors.md).
:::

:::note Sem fallback por variáveis de ambiente
O SDK **não** lê `NFE_API_KEY`/`NFE_DATA_API_KEY` automaticamente — resolva as
variáveis você mesmo (`$_ENV`, `getenv()`, secrets do framework) e passe-as ao
construtor.
:::

## Opções avançadas — somente em `Nfe\Config`

Retry e logger **não** são aceitos diretamente por `new Client(...)`. Eles vivem
em `Nfe\Config`, que é injetado via `config:` (e então substitui todos os
argumentos de conveniência):

```php
use Nfe\Client;
use Nfe\Config;
use Nfe\Http\RetryPolicy;

$nfe = new Client(config: new Config(
    apiKey: $_ENV['NFE_API_KEY'],
    dataApiKey: $_ENV['NFE_DATA_API_KEY'] ?? null,
    timeout: 60,
    retry: new RetryPolicy(maxRetries: 5, baseDelay: 0.5, maxDelay: 20.0, jitter: 0.3),
    logger: $meuLoggerPsr3,           // Psr\Log\LoggerInterface (opcional)
    userAgentSuffix: 'meu-integrador/1.0',
));
```

| Opção | Tipo | Padrão | Descrição |
| --- | --- | --- | --- |
| `retry` | `Nfe\Http\RetryPolicy` | `new RetryPolicy()` | Política de retentativas (veja abaixo). |
| `logger` | `?Psr\Log\LoggerInterface` | `null` | Logger PSR-3 opcional. O pacote `psr/log` **não** é exigido em runtime. |
| `transport` | `?Nfe\Http\Transport` | `null` | Transporte HTTP customizado. |

## Retry (`Nfe\Http\RetryPolicy`)

O transporte retenta automaticamente respostas **HTTP 429** e **5xx** com
backoff exponencial e jitter. O padrão é `maxRetries: 3`, `baseDelay: 1.0`s,
`maxDelay: 30.0`s, `jitter: 0.3` (±30%). Erros 4xx de negócio **não** são
retentados.

```php
use Nfe\Http\RetryPolicy;

// Desabilitar completamente:
$config = new Nfe\Config(apiKey: $key, retry: RetryPolicy::none());
```

## Modelo de duas chaves

A plataforma separa a cobrança entre a API principal (emissão — cobrada por
documento) e os serviços de **dados** (consultas — cobrados por consulta, muitas
vezes em outro plano). As famílias de dados — `addresses` (CEP), `legal-entity`
(CNPJ), `natural-person` (CPF) e `nfe-query` (consultas de NF-e/NFC-e) — usam a
`dataApiKey` quando presente, com **fallback** para a `apiKey`. Todas as demais
famílias usam a `apiKey`.

```php
$nfe = new Nfe\Client(
    apiKey: 'chave-de-emissao',
    dataApiKey: 'chave-de-consulta',
);

// - $nfe->addresses           → usa dataApiKey (família de dados)
// - $nfe->legalEntityLookup   → usa dataApiKey
// - $nfe->serviceInvoices     → usa apiKey (emissão)
// - $nfe->companies           → usa apiKey
```

:::warning `api.nfse.io` usa a chave principal
`taxCalculation`, `taxCodes`, `transportationInvoices` e
`inboundProductInvoices` (host `api.nfse.io`) **não** são famílias de dados:
sempre usam a `apiKey`. Um 403 nas famílias de dados com uma chave principal
válida normalmente significa que o plano da chave não inclui o produto de
dados — informe uma `dataApiKey` provisionada. Veja
[Roteamento multi-host](./multi-host-routing.md).
:::

## Overrides por requisição (`Nfe\Http\RequestOptions`)

Todo método de recurso aceita um `RequestOptions` opcional como último argumento
para sobrescrever chave, host ou timeout **de uma única chamada** — útil em
integrações multi-tenant:

```php
use Nfe\Http\RequestOptions;

$invoice = $nfe->serviceInvoices->retrieve(
    $companyId,
    $invoiceId,
    new RequestOptions(apiKey: $chaveDoTenant, timeout: 120),
);
```

| Campo | Efeito |
| --- | --- |
| `apiKey` | Substitui a chave resolvida para esta chamada. |
| `baseUrl` | Aponta a chamada para outro host (mock, proxy, ambiente próprio). |
| `timeout` | Timeout específico desta chamada, em segundos. |

:::note Sem `idempotencyKey`
A API da NFE.io não honra o header `Idempotency-Key` atualmente, então o
`RequestOptions` não expõe esse campo. Quando a API adicionar suporte, ele
entrará em uma release menor aditiva.
:::

## Sandbox vs. Produção

:::warning A separação produção vs. teste fica na conta, não no SDK
A escolha entre **produção** e **teste (homologação)** é definida na
configuração da sua conta em [app.nfe.io](https://app.nfe.io) (lado servidor) —
**não** pela chave de API nem pelo SDK.
:::

O enum `Nfe\Environment` (`Production` / `Sandbox`) é aceito e validado, mas
hoje **não altera** endpoints, chaves ou comportamento — está reservado para uso
futuro.

:::note Ambiente SEFAZ é outra coisa
Os recursos de produto e consumidor (NF-e/NFC-e) aceitam um parâmetro
**separado** `environment` do tipo `string` (`"Production"` / `"Test"`) nas
operações de listagem — esse é o ambiente da SEFAZ e é tratado nos guias
daqueles recursos, não aqui.
:::

## Transporte PSR-18 (Guzzle, Symfony HttpClient…)

Por padrão o SDK fala HTTP com cURL (`Nfe\Http\CurlTransport`). Para rotear pelo
cliente HTTP do seu framework, use `Nfe\Http\Psr18Transport` com qualquer
cliente PSR-18 + fábricas PSR-17 (exige `psr/http-client` e `psr/http-factory`
instalados no seu projeto):

```php
use Nfe\Client;
use Nfe\Config;
use Nfe\Http\Psr18Transport;

$transport = new Psr18Transport($psr18Client, $requestFactory, $streamFactory);
$nfe = new Client(config: new Config(apiKey: $key, transport: $transport));
```

## Próximos passos

- [Emissão assíncrona e polling](./async-and-polling.md) — o contrato HTTP 202.
- [Tratamento de erros](./errors.md) — `catch` por tipo.
- [Roteamento multi-host](./multi-host-routing.md) — hosts e chaves por família.
