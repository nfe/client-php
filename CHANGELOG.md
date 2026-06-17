# Changelog

Todas as mudanças relevantes deste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/),
e este projeto segue [Versionamento Semântico](https://semver.org/lang/pt-BR/spec/v2.0.0.html).

## [Unreleased] — v3 (em desenvolvimento)

### Adicionado

#### Fundação e tooling

- Baseline em PHP 8.2+. Encerra suporte às versões 5.4 até 8.1.
- Autoload PSR-4 com namespace raiz `Nfe\` em `src/`.
- Pacote Composer renomeado: `nfe/client-php` (antes `nfe/nfe`). Os dois
  podem coexistir durante a migração. O `nfe/nfe` está congelado na v2.5.
- `declare(strict_types=1)` em todos os arquivos-fonte.
- Pest 3, PHPStan nível 8 e PHP-CS-Fixer (PER-CS 2.0 + PHP 8.2 migration)
  configurados em `require-dev` e impostos pelo CI.
- Matriz de CI do GitHub Actions em PHP 8.2 / 8.3 / 8.4.

#### Cliente, transporte e configuração

- `Nfe\Client` no estilo Stripe: ponto de entrada único com propriedades
  tipadas para cada recurso. Sem estado global, sem chamadas estáticas.
- `Nfe\Config` imutável (`final readonly`) e `Nfe\Environment` (Production
  / Sandbox).
- `Config::baseUrlForApi($familia)` roteia recursos para seis hosts
  distintos (api.nfe.io, address.api.nfe.io/v2, nfe.api.nfe.io,
  legalentity.api.nfe.io, naturalperson.api.nfe.io, api.nfse.io) —
  paritário com o mapeamento canônico do SDK Node.
- `dataApiKey` opcional em `Config` e `Client` para a API de serviços de
  dados (consultas de CEP/CNPJ/CPF, query de NF-e/NFC-e). Quando definida,
  o SDK roteia as famílias de recurso correspondentes para a chave de
  dados; quando `null`, faz fallback para `apiKey` — espelha a cadeia
  `resolveDataApiKey()` do SDK Node.
- Pilha HTTP zero-dependência: `Nfe\Http\CurlTransport` (transporte cURL
  nativo) + `Nfe\Http\RetryingTransport` (retry com backoff exponencial
  e jitter simétrico, defaults alinhados ao Stripe-PHP).
- `Nfe\Http\RequestOptions` permite override por-chamada de `apiKey`,
  `baseUrl` e `timeout` (útil em integrações multi-tenant).
- Slot opcional para `Psr\Log\LoggerInterface` (PSR-3) e adaptador
  opcional para `Psr\Http\Client\ClientInterface` (PSR-18) — nenhum dos
  dois é dependência em runtime.

#### Recursos (17 propriedades, paridade 1:1 com o SDK Node + 1 paridade-plus)

- **Emissão de notas (5):** `serviceInvoices` (NFS-e), `productInvoices`
  (NF-e), `consumerInvoices` (NFC-e — paridade-plus, além do Node SDK),
  `transportationInvoices` (CT-e), `inboundProductInvoices` (NF-e de
  entrada).
- **Consulta de notas (2):** `productInvoiceQuery`, `consumerInvoiceQuery`.
- **Entidades (4):** `companies`, `legalPeople`, `naturalPeople`,
  `webhooks`.
- **Consultas de dados (3):** `addresses` (CEP), `legalEntityLookup`
  (CNPJ), `naturalPersonLookup` (CPF).
- **Tributação (3):** `taxCalculation`, `taxCodes`, `stateTaxes`.

#### Tipos de resposta discriminados (HTTP 202 vs 201)

- Interfaces `Nfe\Response\Pending` e `Nfe\Response\Issued<T>` modelam a
  resposta assíncrona da NFE.io. Cada família de emissão (Service,
  Product, Consumer) tem suas implementações concretas
  (`ServiceInvoicePending`/`ServiceInvoiceIssued`, etc.) com `resource(): T`
  tipado.
- `Nfe\Util\FlowStatus::TERMINAL` lista os quatro estados terminais
  (`Issued`, `IssueFailed`, `Cancelled`, `CancelFailed`) — espelha
  `TERMINAL_FLOW_STATES` do SDK Node.
- `Nfe\Util\ListResponse<T>` + `ListPage` — wrapper de listagem (page-style
  ou cursor-style).

#### Hierarquia de exceções tipadas

- `Nfe\Exception\ApiErrorException` como base, com subclasses:
  `InvalidRequestException` (400), `AuthenticationException` (401),
  `AuthorizationException` (403), `NotFoundException` (404),
  `RateLimitException` (429), `ServerException` (5xx).
- `Nfe\Exception\ApiConnectionException` para falhas de rede/DNS/TLS.
- `Nfe\Exception\SignatureVerificationException` para assinaturas de
  webhook inválidas.
- Toda resposta não-2xx é mapeada automaticamente para a subclasse tipada
  correspondente na camada de recurso. Antes, respostas 5xx podiam
  emergir como DTOs com tudo `null`; agora geram exceção.

#### Webhooks

- `Nfe\Webhook::constructEvent(payload, sigHeader, secret): WebhookEvent`
  — preferido; valida HMAC-SHA1 sobre `X-Hub-Signature` com comparação
  timing-safe (`hash_equals`) e retorna o evento parseado.
- `Nfe\Webhook::verifySignature()` — variante low-level, paridade com
  `validateSignature` do SDK Node.
- `Nfe\WebhookEvent` DTO readonly (`type`, `data`, `id`, `createdAt`).
- Esquema confirmado com a API NFE.io em 2026-05-13.

#### Pipeline de codegen OpenAPI

- Specs OpenAPI versionadas em `openapi/` (12 specs gerando 624 DTOs;
  5 specs Swagger 2.0 e 1 sem schemas ficam fora do codegen e usam DTOs
  hand-written em `src/Resource/Dto/`).
- Geração via `scripts/Generator` (`symfony/yaml` + `nikic/php-parser`,
  ambos `require-dev`). DTOs gerados em `src/Generated/` — **nunca
  edite à mão**.
- Comandos `composer generate` (regenera) e `composer generate:check`
  (verifica drift no CI).

#### Helpers utilitários

- `Nfe\Util\IdValidator` — validação fail-fast para `companyId`,
  `invoiceId`, `accessKey` (44 dígitos), `stateTaxId`, `eventKey`, `cnpj`,
  `cpf`, `cep`, `state` (UF 2 letras).
- `Nfe\Util\DateNormalizer` — converte `string|DateTimeImmutable` em
  `YYYY-MM-DD` para endpoints que aceitam ambos os formatos.
- `Nfe\Util\UserAgent` — header padronizado `Nfe-PHP/<versão>` com sufixo
  opcional configurável (ex.: `WHMCS/8.10`).

### Alterado

- O desenvolvimento agora ocorre na branch `v3`. A `master` está congelada
  na v2.5.

### Removido

- `lib/NFe/*` (código legado autoloaded por classmap).
- Integração com runner `test/simpletest/*`. Pest substitui o SimpleTest.
- `composer.phar` versionado no repositório.
- `.travis.yml` (substituído por `.github/workflows/ci.yml`).

## [2.5] e anteriores

Veja o histórico git na branch `master`. A linha v2 não recebe mais manutenção.
