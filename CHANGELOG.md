# Changelog

Todas as mudanças relevantes deste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/),
e este projeto segue [Versionamento Semântico](https://semver.org/lang/pt-BR/spec/v2.0.0.html).

## [Unreleased]

### ⚠️ Mudança de comportamento — retry de `POST`

- O transporte **não retenta mais requisições `POST` em respostas HTTP 5xx** nem
  em falhas de rede ambíguas (timeout após o envio). Reexecutar um `POST` pode
  duplicar o efeito — na emissão de NFS-e, isso significa **nota fiscal
  duplicada**. Sonda ao vivo (2026-07-06) reproduziu o risco: um `create()`
  retornou `HTTP 500` e ainda assim emitiu a nota. `POST` só é retentado agora
  em `429`, em falha comprovada de conexão (a requisição nunca chegou ao
  servidor) ou quando carrega um header `Idempotency-Key`. Métodos idempotentes
  (`GET`/`PUT`/`DELETE`/`HEAD`/`OPTIONS`) seguem retentando como antes.
- **Rotas de escape**: para emissão retry-safe, envie um `externalId` e reconcilie
  com `findByExternalId()` após uma falha ambígua (veja *Adicionado*); para
  restaurar o retry por chamada, passe `RequestOptions(retry: ...)`.

### Adicionado

- Retry ciente de método/idempotência em `Nfe\Http\RetryingTransport` (tabela de
  decisão por método × status × fase da falha × header `Idempotency-Key`)
  (`add-safe-retry-idempotency`).
- `Nfe\Http\FailurePhase` (`ConnectionNotEstablished` / `RequestMaybeSent`) e os
  campos `failurePhase`/`curlErrno` em `Nfe\Exception\ApiConnectionException`:
  `CurlTransport` classifica por allowlist de errnos (5/6/7/35 → conexão não
  estabelecida) e `Psr18Transport` por `NetworkExceptionInterface`.
- Override de retry por requisição: `Nfe\Http\RequestOptions` ganha o campo
  `retry` (`?RetryPolicy`), permitindo ligar/desligar o retry de uma única
  chamada sem manter dois clients. O decorator de retry passa a envolver sempre
  o transporte base.
- `ServiceInvoicesResource::findByExternalId($companyId, $externalId)` — recupera
  a NFS-e pela chave do integrador via rota dedicada
  `GET /v1/companies/{id}/serviceinvoices/external/{externalId}` (hit = envelope
  de coleção; miss = `null`; confirmado ao vivo em 2026-07-06).
- `ServiceInvoicesResource::isDuplicateExternalId(ApiErrorException $e): bool` —
  reconhece a rejeição `400 "service invoice with external id (…) already exists"`.
- Campo `externalId` no DTO `Nfe\Resource\Dto\ServiceInvoices\ServiceInvoice`.

## [3.1.0] — 2026-07-03

### Adicionado

- Métodos account-scoped em `WebhooksResource`, alinhados ao contrato real da
  API (`/v2/webhooks`, confirmado por sondas ao vivo em 3 contas,
  2026-07-02/03): `listAccountWebhooks()`, `createAccountWebhook()`,
  `retrieveAccountWebhook()`, `updateAccountWebhook()`,
  `deleteAccountWebhook()`, `deleteAllAccountWebhooks()` (destrutivo — remove
  TODOS os webhooks da conta), `pingAccountWebhook()` e `fetchEventTypes()`
  (lista viva de event types). Create/update enviam o envelope obrigatório
  `{"webHook": {...}}` e as respostas envelopadas são desembrulhadas
  (`fix-account-webhooks-contract`).
- Novo DTO `Nfe\Resource\Dto\Webhooks\AccountWebhook` com o shape real do
  recurso: `id`, `uri`, `contentType`, `secret` (32–64 chars; ecoado no
  create, omitido nas leituras), `filters`, `insecureSsl`, `headers`,
  `properties`, `status`, `createdOn`, `modifiedOn`, `raw`. Nota de contrato:
  o spec OpenAPI declara `contentType`/`status` como enum int, mas a API
  serializa strings (`"json"`, `"Active"`) — o DTO segue o fio, e um teste de
  alinhamento YAML↔DTO pina o desvio.

### Depreciado

- Os métodos company-scoped de `WebhooksResource` (`list`, `create`,
  `retrieve`, `update`, `delete`, `test`): a rota
  `/v1/companies/{id}/webhooks` retorna **404 incondicional** na API atual
  (o contrato havia sido herdado de alucinação do SDK Node). Comportamento
  inalterado; use os equivalentes `*AccountWebhook*`. Remoção na próxima
  major.
- `WebhooksResource::getAvailableEvents()`: os literais `invoice.*` não
  existem na API — os ids reais seguem `service_invoice.*` /
  `product_invoice.*` / `consumer_invoice.*`. Use `fetchEventTypes()`.
- DTO `Nfe\Resource\Dto\Webhooks\Webhook` (`url`/`events`): shape rejeitado
  pela API (`400 "The Uri field is required"`). Use `AccountWebhook`.

### Documentação

- Gotchas do contrato real documentados no phpdoc, README e
  `docs/(recursos/)webhooks.md`: a NFE.io **pinga a `uri` na criação** e
  exige resposta 2xx; `PUT` de update é **substituição integral** (update sem
  `status` desativa o webhook — monte o corpo a partir do retrieve).

## [3.0.0] — 2026-07-01

### Removido

- **BREAKING:** `AddressesResource::search()` e `AddressesResource::lookupByTerm()`
  foram removidos. O host `address.api.nfe.io/v2` suporta **apenas** consulta por
  CEP; os endpoints `GET /addresses` (busca) e `GET /addresses/{termo}` não
  existem (retornam 404), então os métodos nunca funcionaram. Paridade com o
  SDK Node (`fix-address-lookup-api-mismatch`). Use `lookupByPostalCode()`.

### Corrigido

- `AddressesResource::lookupByPostalCode()` agora desembrulha o envelope real
  `{ "address": { … } }` retornado pela API. Antes, `extractAddresses()`
  procurava a chave `addresses` (plural) — que a API nunca retorna — e caía num
  fallback que expunha o envelope inteiro, deixando `->addresses[0]['street']`
  como `null` (quebra silenciosa). Agora os campos do endereço ficam legíveis
  diretamente em `->addresses[0]`.
- `openapi/consulta-endereco-v3.yaml` enxugado para conter só a operação de CEP,
  alinhando o spec à API real (as operações de busca/termo eram fantasmas).

## [3.0.0-rc.2] — 2026-07-01

### Corrigido

- `composer.json` `name` voltou a ser `nfe/nfe` (a `v3.0.0-rc.1` shipou
  com `nfe/client-php` por engano). A v3 mantém o **mesmo slug Packagist**
  da v2 — Composer resolve `^2.0` e `^3.0` para a major correta. Não há
  pacote `nfe/client-php` no Packagist; a `v3.0.0-rc.1` foi indexada
  corretamente sob `nfe/nfe`. README e MIGRATION alinhados.

## [3.0.0-rc.1] — 2026-06-30

### Corrigido

- `TaxCodesResource` apontava para `/tax-rules/...` (plural); a API expõe
  esses endpoints em `/tax-codes/...` (singular, paridade com o SDK Node).
  Todos os quatro métodos (`listOperationCodes`, `listAcquisitionPurposes`,
  `listIssuerTaxProfiles`, `listRecipientTaxProfiles`) retornavam 404.
  Descoberto via smoke real e corrigido em 2026-06-27.
- `AbstractResource::download()` agora segue HTTP 302/303/307 com header
  `Location` em uma segunda requisição cURL plain, **sem** enviar
  `Authorization` ao CDN. A API NFE.io responde 302 + Location para o S3
  pré-assinado em todos os downloads de PDF/XML; antes, o SDK tratava o
  3xx como falha e lançava `InvalidRequestException`.
- `AbstractResource::send()` agora só auto-throwa em `>= 400`. 3xx flui
  para o caller, permitindo que `download()` siga o redirect manualmente.
  2xx (incluindo 202) continua fluindo como antes.
- `CompaniesResource::listAll()` agora começa em `pageIndex = 1`. A API da
  NFE.io usa paginação **1-based** (`pageIndex >= 1`); o valor antigo `0`
  causava HTTP 400 (`"pageIndex must be greater or equal to 1"`) na primeira
  chamada. Descoberto via smoke test contra sandbox real.
- `CompaniesResource::listAll()` usa `pageSize = 50` (era 100). A API rejeita
  `pageCount > 50` com HTTP 400 (`"pageCount must be between 1 and 50"`).
- Exemplos do README e `samples/service-invoice-list.php` atualizados para
  refletir a paginação 1-based. Docblocks de `list()` em `CompaniesResource`
  e `ServiceInvoicesResource` agora documentam explicitamente o valor mínimo.

### Adicionado

#### Fundação e tooling

- Baseline em PHP 8.2+. Encerra suporte às versões 5.4 até 8.1.
- Autoload PSR-4 com namespace raiz `Nfe\` em `src/`.
- Mesmo pacote Composer da v2 (`nfe/nfe`), agora servindo a major 3.
  A v2 permanece disponível via constraint `^2.0`; a v3 via `^3.0`.
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
