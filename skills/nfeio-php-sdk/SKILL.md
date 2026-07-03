---
name: nfeio-php-sdk
description: "NFE.io PHP SDK integration expert (Composer package: nfe/nfe, namespace Nfe\\). MUST trigger when: composer.json requires 'nfe/nfe' or code has `use Nfe\\Client`, `new Nfe\\Client(`, or references the Nfe\\ namespace; user mentions NFE.io, NFS-e, NF-e, NFC-e, CT-e, CFe-SAT, nota fiscal, nota fiscal eletronica, nota fiscal de servico, Brazilian invoice, fiscal document, electronic invoice Brazil, CNPJ lookup, CPF lookup, consulta CEP, service invoice, product invoice, consumer invoice, transportation invoice, tax calculation Brazilian taxes, SEFAZ, emissao de nota; user builds Brazilian electronic fiscal document automation or tax compliance in PHP/Laravel/Symfony/WHMCS/WooCommerce; user mentions polling for invoice status, async invoice processing, webhook signature validation, or certificate management for Brazilian fiscal documents. Covers all 17 SDK resources, async invoice creation via native union return types (instanceof), MANUAL polling (there is no createAndWait), the Nfe\\Exception hierarchy, 1-based pagination, string-byte downloads, the static Nfe\\Webhook signature helper, dual API keys (dataApiKey), CNPJ/CPF/CEP lookups, and tax calculation. Use this skill even if the user doesn't name the SDK -- if they build Brazilian fiscal document automation in PHP, it applies. Note: this is the PHP SDK; its idioms differ from the Node SDK (nfe-io) -- do NOT port Node patterns blindly."
---

# NFE.io PHP SDK Integration Guide

This skill enables correct, production-ready code with the NFE.io PHP SDK for Brazilian electronic fiscal documents: NFS-e (service invoices), NF-e (product invoices), NFC-e (consumer invoices), CT-e (transport), plus CNPJ/CPF/CEP lookups and tax calculation.

**PHP is not Node.** The SDK has conceptual 1:1 parity with the Node SDK (`nfe-io`) but its idioms differ in ways that break a naive port. The most important: **there is no `createAndWait()`** — you poll manually. See *Critical Pitfalls*.

## Package & Import

Composer package: **`nfe/nfe`** (the GitHub repo is `nfe/client-php`; the Packagist name is `nfe/nfe`).

```bash
composer require nfe/nfe
```

```php
use Nfe\Client;

$nfe = new Nfe\Client(apiKey: $_ENV['NFE_API_KEY']);
$invoice = $nfe->serviceInvoices->retrieve($companyId, $invoiceId);
```

Requirements: **PHP 8.2+**, extensions `curl`, `json`, `mbstring`. **Zero runtime dependencies** (uses cURL directly). PSR-3 logger and PSR-18 transport are optional.

## Quick Start

The constructor takes **named arguments** (there is no options-object/array):

```php
use Nfe\Client;
use Nfe\Environment;

$nfe = new Nfe\Client(
    apiKey: $_ENV['NFE_API_KEY'],        // required (unless you pass a Config)
    dataApiKey: $_ENV['NFE_DATA_API_KEY'] ?? null, // optional, for data services (see below)
    environment: Environment::Production, // Environment::Production | Environment::Sandbox
    timeout: 60,                          // seconds (default 60)
    userAgentSuffix: 'my-integrator/1.0', // optional
);
```

Auth: the SDK sends `Authorization: Basic <apiKey>` on every request.

For full control, build a `Nfe\Config` and pass it as `config:` (overrides the convenience args). `Config` also accepts a `Nfe\Http\RetryPolicy` (default: 3 retries, base 1s, max 30s, jitter 0.3) and a PSR-3 `logger`.

**Dual API keys**: data-service resources (`addresses`, `legalEntityLookup`, `naturalPersonLookup`, `productInvoiceQuery`/`consumerInvoiceQuery`) use `dataApiKey`, falling back to `apiKey` when it is null. **Only those four families honor `dataApiKey`** — see Pitfall #12.

## Resource Map

All 17 resources are bound eagerly as `public readonly` properties on `Nfe\Client`. Access them directly (`$nfe->serviceInvoices`).

| Accessor | Resource | Host / version | Scope |
|----------|----------|----------------|-------|
| `$nfe->serviceInvoices` | NFS-e service invoices | api.nfe.io /v1 | Company |
| `$nfe->productInvoices` | NF-e product invoices | api.nfse.io /v2 | Company |
| `$nfe->consumerInvoices` | NFC-e consumer invoices | api.nfse.io /v2 | Company |
| `$nfe->transportationInvoices` | CT-e inbound transport | api.nfse.io /v2 | Company |
| `$nfe->inboundProductInvoices` | Inbound NF-e (DFe distribution) | api.nfse.io /v2 | Company |
| `$nfe->productInvoiceQuery` | NF-e query by access key | nfe.api.nfe.io | Global |
| `$nfe->consumerInvoiceQuery` | CFe-SAT/NFC-e coupon query | nfe.api.nfe.io | Global |
| `$nfe->companies` | Companies | api.nfe.io /v1 | Account |
| `$nfe->legalPeople` | Legal people (PJ) | api.nfe.io /v1 | Company |
| `$nfe->naturalPeople` | Natural people (PF) | api.nfe.io /v1 | Company |
| `$nfe->webhooks` | Webhooks CRUD | api.nfe.io /v2 | Account |
| `$nfe->addresses` | Address lookup (CEP only) | address.api.nfe.io/v2 | Global |
| `$nfe->legalEntityLookup` | CNPJ lookup | legalentity.api.nfe.io | Global |
| `$nfe->naturalPersonLookup` | CPF lookup | naturalperson.api.nfe.io | Global |
| `$nfe->taxCalculation` | Tax engine | api.nfse.io /v2 | Tenant |
| `$nfe->taxCodes` | Tax code reference | api.nfse.io /v2 | Global |
| `$nfe->stateTaxes` | State tax (IE) registrations | api.nfse.io /v2 | Company |

Plus a **static** helper, **`Nfe\Webhook`** — NOT `$nfe->webhooks` — for signature validation (`verifySignature`, `constructEvent`).

**Not available in the PHP SDK** (exist in the Node SDK but have no first-class resource here): RTC (Reforma Tributária) service/product invoices, `municipalTaxes`, `certificates`-by-thumbprint, `notifications`. Do not generate calls for these.

**Company-scoped** resources take `$companyId` as the first argument of every method. **Global** resources do not. `companies` is **Account**-scoped (no `$companyId`). `taxCalculation` is **Tenant**-scoped (`$tenantId`).

## Core Pattern: Company-Scoped Operations

```php
// List service invoices for a company (pageIndex is 1-based — see Pitfall #6)
$page = $nfe->serviceInvoices->list($companyId, ['pageIndex' => 1, 'pageCount' => 50]);
foreach ($page->data as $invoice) { /* $invoice is a ServiceInvoice DTO */ }

// Create a legal person under a company
$person = $nfe->legalPeople->create($companyId, [
    'federalTaxNumber' => 11444555000149, // int for companies/legalPeople; string for naturalPeople (Pitfall #11)
    'name' => 'Example Company Ltda',
    'email' => 'contact@example.com',
]);
```

## Core Pattern: Async Invoice Creation (Critical)

`create()` returns a **native PHP union type** — `ServiceInvoicePending|ServiceInvoiceIssued` (and the product/consumer equivalents). There is **no** `{ status: 'immediate' | 'async' }` discriminator object like the Node SDK. Discriminate with `instanceof` against the marker interfaces `Nfe\Response\Pending` / `Nfe\Response\Issued`:

```php
use Nfe\Response\Pending;

$result = $nfe->serviceInvoices->create($companyId, [
    'cityServiceCode' => '2690',
    'description'     => 'IT Consulting Services',
    'servicesAmount'  => 1500.00,
    'borrower'        => [
        'federalTaxNumber' => 11444555000149,
        'name'  => 'Client Company',
        'email' => 'client@example.com',
    ],
]);

if ($result instanceof Pending) {
    // HTTP 202: only invoiceId() + location() are available (no invoice body yet).
    $invoiceId = $result->invoiceId();
    // ... poll (see next pattern)
} else {
    // HTTP 201: issued synchronously. $result is ServiceInvoiceIssued.
    $invoice = $result->resource(); // ServiceInvoice DTO
}
```

`createWithStateTax($companyId, $stateTaxId, $data)` exists on `productInvoices` and `consumerInvoices` (not `serviceInvoices`), with the same union return.

## Core Pattern: Manual Polling (there is no createAndWait)

**The SDK has no `createAndWait()`, `pollUntilComplete()`, or any wait helper** (deferred post-v3.0). You write the loop yourself:

```php
use Nfe\Response\Pending;
use Nfe\Util\FlowStatus;

$result = $nfe->serviceInvoices->create($companyId, $data);

if ($result instanceof Pending) {
    $invoiceId = $result->invoiceId();
    do {
        sleep(3); // back off yourself; some municipalities take minutes
        $status = $nfe->serviceInvoices->getStatus($companyId, $invoiceId); // array: flowStatus/flowMessage
        $flow = $status['flowStatus'] ?? '';
    } while (!FlowStatus::isTerminal($flow));

    // isTerminal is TRUE for success AND failure — inspect the actual status.
    if ($flow === 'Issued') {
        $invoice = $nfe->serviceInvoices->retrieve($companyId, $invoiceId);
    } else {
        // 'IssueFailed' | 'Cancelled' | 'CancelFailed'
    }
}
```

- **Terminal** (`FlowStatus::TERMINAL`): `Issued`, `IssueFailed`, `Cancelled`, `CancelFailed`.
- **Non-terminal** (keep polling): `WaitingSend`, `WaitingReturn`, `WaitingDownload`, `WaitingCalculateTaxes`, `WaitingDefineRpsNumber`, `WaitingSendCancel`, `PullFromCityHall`.
- **`getStatus()` exists only on `serviceInvoices`.** For `productInvoices`/`consumerInvoices` there is no `getStatus()` — poll with `retrieve()` and read the DTO's flow field. (Product/consumer emission is webhook-driven; prefer webhooks over polling there.)

## Core Pattern: Error Handling

Every API error extends **`Nfe\Exception\ApiErrorException`** (which extends `RuntimeException`). There are **no** `isXxx()` type-guard functions — use `instanceof`.

| Exception (`Nfe\Exception\…`) | When |
|---|---|
| `AuthenticationException` | 401 — invalid/missing key |
| `AuthorizationException` | 403 — key lacks product/plan |
| `InvalidRequestException` | 400/422 + local validation (thrown synchronously before the request) |
| `NotFoundException` | 404 |
| `RateLimitException` | 429 |
| `ServerException` | 5xx |
| `ApiConnectionException` | network/cURL failure |
| `SignatureVerificationException` | webhook signature mismatch |

```php
use Nfe\Exception\ApiErrorException;
use Nfe\Exception\AuthenticationException;
use Nfe\Exception\InvalidRequestException;

try {
    $result = $nfe->serviceInvoices->create($companyId, $data);
} catch (AuthenticationException $e) {
    // check API key
} catch (InvalidRequestException $e) {
    // invalid data
} catch (ApiErrorException $e) {
    // base: $e->statusCode, $e->responseBody, $e->responseHeaders, $e->errorCode, $e->getMessage()
}
```

`Nfe\Exception\ErrorFactory::fromResponse($response)` maps an HTTP response to the right exception (used internally).

## Core Pattern: Pagination

`list()` returns `Nfe\Util\ListResponse` — `->data` (list of DTOs) and `->page` (a `Nfe\Util\ListPage`). **There is no auto-paginating iterator** — re-call `list()` with the next page yourself.

- **Page-style** (`serviceInvoices`, `consumerInvoices`, `companies`, `taxCodes`): `['pageIndex' => 1, 'pageCount' => 50]`. **`pageIndex` is 1-based** (Pitfall #6). `taxCodes` caps `pageCount` at 50.
- **Cursor-style** (`productInvoices`, `stateTaxes`): `['startingAfter' => $id, 'limit' => 25, ...]`. `productInvoices->list()` **requires** `environment` and its `$options` argument is **mandatory** (no default).

```php
$page = $nfe->companies->list(['pageIndex' => 1, 'pageCount' => 50]);
$page->data;        // Company[]
$page->page;        // ListPage: pageIndex/pageCount/total (page-style) or startingAfter/endingBefore (cursor)

// companies also offers a client-side listAll() that walks pages for you:
$all = $nfe->companies->listAll(); // array<Company>
```

## Core Pattern: File Downloads

All `download*()` / `getPdf`/`getXml` methods return **raw bytes as a PHP `string`** (not a stream/Buffer). Write with `file_put_contents()`:

```php
file_put_contents('invoice.pdf', $nfe->serviceInvoices->downloadPdf($companyId, $invoiceId));
file_put_contents('invoice.xml', $nfe->serviceInvoices->downloadXml($companyId, $invoiceId));
$danfe = $nfe->productInvoiceQuery->downloadPdf($accessKey); // string bytes
```

Internally, downloads transparently follow a 302/303/307 redirect to the CDN in a second request that omits the `Authorization` header (so your key never reaches the S3 host).

## Core Pattern: Webhooks

CRUD lives on `$nfe->webhooks` and is **account-scoped** (`/v2/webhooks` — all companies of the account fire to the same targets): `listAccountWebhooks()`, `createAccountWebhook($data)`, `retrieveAccountWebhook($id)`, `updateAccountWebhook($id, $data)`, `deleteAccountWebhook($id)`, `deleteAllAccountWebhooks()` (destructive — removes ALL), `pingAccountWebhook($id)`, `fetchEventTypes()`. Gotchas: NFE.io **pings the `uri` on create and requires a 2xx**; `secret` is 32–64 chars, echoed only on create; **update is a full PUT replacement** — omitting `status` deactivates the hook, so build the body from a fresh retrieve. The company-scoped `list`/`create`/`retrieve`/`update`/`delete`/`test($companyId, …)` methods are `@deprecated` (their `/v1/companies/{id}/webhooks` route 404s on the live API).

**Signature validation lives on the static `Nfe\Webhook` class** (do not look for `$nfe->webhooks->verifySignature()` — it does not exist):

```php
use Nfe\Webhook;

// In your webhook handler — pass the RAW request body bytes.
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '';

$ok = Nfe\Webhook::verifySignature($payload, $signature, $secret); // bool; HMAC-SHA1 by default
// Or unwrap the v2 { action, payload } envelope into a typed event (throws on bad signature):
$event = Nfe\Webhook::constructEvent($payload, $signature, $secret); // Nfe\WebhookEvent
```

`verifySignature()` accepts both prefixed (`sha1=…`) and bare hex, and takes an optional `$algo` (default `'sha1'`). Event-type ids follow `resource.event_action` (e.g. `service_invoice.issued_successfully`) — fetch the live list with `$nfe->webhooks->fetchEventTypes()`; the hard-coded `getAvailableEvents()` is `@deprecated` (its `invoice.*` literals do not exist on the live API).

## Critical Pitfalls

Verified against the SDK source. These are exactly where a Node-to-PHP port goes wrong.

1. **Composer package is `nfe/nfe`**, not `nfe/client-php` (that's the GitHub repo slug, used only for the skill install).
2. **No `createAndWait`/polling helper** — poll manually with `getStatus()`/`retrieve()` + `FlowStatus::isTerminal()`. (See the polling pattern.)
3. **`create()` returns a native union** (`…Pending|…Issued`) discriminated by `instanceof Nfe\Response\Pending`/`Issued` — not a `status` field.
4. **A `Pending` has no invoice body** — only `invoiceId()` + `location()`. You cannot read tax values off it; retrieve after it's terminal.
5. **`FlowStatus::isTerminal()` is true for failure too** (`IssueFailed`/`CancelFailed`) — check the actual status to know if it succeeded.
6. **`pageIndex` is 1-based** for `serviceInvoices`/`consumerInvoices`. Ignore the `Nfe\Util\ListPage` docblock that says "0-based" — the value is passed through and read back verbatim (1-based in practice).
7. **`productInvoices->list()` requires `environment`** and its `$options` arg is mandatory (cursor pagination). `serviceInvoices`/`consumerInvoices` use page-style with optional `$options`.
8. **`getStatus()` exists only on `serviceInvoices`** — not on product/consumer.
9. **Downloads return `string` bytes**, not a Buffer/stream.
10. **Deletion method names differ**: `companies->remove()` returns `array{deleted, id}`; `legalPeople`/`naturalPeople`/`stateTaxes` use `delete(): void`; webhooks use `deleteAccountWebhook($id): void` — and `deleteAllAccountWebhooks()` wipes EVERY webhook on the account, so never confuse the two.
11. **`federalTaxNumber` DTO type varies**: `Company` and `LegalPerson` = `?int`; `NaturalPerson` = `?string`. The SDK does no create-side validation/coercion — send the type the API expects (int for company/legal person).
12. **`dataApiKey` only applies to `addresses`, `legalEntityLookup`, `naturalPersonLookup`, and NF-e/NFC-e query.** `taxCalculation`, `transportationInvoices`, `inboundProductInvoices` always use the main `apiKey` — porting Node code that relied on `dataApiKey` for `api.nfse.io` resources will silently send the main key.
13. **Certificate upload is not implemented** — `companies` has only read-side cert helpers (`getCertificateStatus`, `checkCertificateExpiration`, `getCompaniesWith[Expiring]Certificates`). No `uploadCertificate`/`validateCertificate` (transport is JSON-only, no multipart).
14. **`addresses` is CEP-only** — `lookupByPostalCode()` is the only method. `search()`/`lookupByTerm()` were removed (those endpoints 404). The response envelope `{ address }` is unwrapped into `->addresses` (a 1-element list).
15. **`createBatch` (`legalPeople`/`naturalPeople`) is sequential** — slower than Node's concurrent batch for large lists.
16. **Webhook signature validation is the static `Nfe\Webhook`** — pass the raw `php://input` body, not a re-encoded array.

## Decision Tree: "I want to…"

| Goal | Resource & method |
|------|-------------------|
| Issue a service invoice (NFS-e) | `$nfe->serviceInvoices->create($companyId, $data)` + manual poll |
| Issue a product invoice (NF-e) | `$nfe->productInvoices->create($companyId, $data)` (+ webhook) |
| Issue NF-e scoped to a state tax | `$nfe->productInvoices->createWithStateTax($companyId, $stateTaxId, $data)` |
| Issue a consumer invoice (NFC-e) | `$nfe->consumerInvoices->create($companyId, $data)` |
| Query an existing NF-e by access key | `$nfe->productInvoiceQuery->retrieve($accessKey)` |
| Query a CFe-SAT/NFC-e coupon | `$nfe->consumerInvoiceQuery->retrieve($accessKey)` |
| Auto-fetch inbound NF-e | `$nfe->inboundProductInvoices->enableAutoFetch($companyId, $data)` |
| Enable inbound CT-e | `$nfe->transportationInvoices->enable($companyId, $data)` |
| Look up a CNPJ | `$nfe->legalEntityLookup->getBasicInfo($cnpj)` |
| Look up a CPF | `$nfe->naturalPersonLookup->getStatus($cpf, $birthDate)` |
| Look up an address by CEP | `$nfe->addresses->lookupByPostalCode('01310-100')` |
| Calculate Brazilian taxes | `$nfe->taxCalculation->calculate($tenantId, $request)` |
| Manage companies & read cert status | `$nfe->companies->*` |
| Manage people (PJ/PF) | `$nfe->legalPeople->*` / `$nfe->naturalPeople->*` |
| Set up webhook notifications | `$nfe->webhooks->createAccountWebhook($data)` (account-scoped; API pings the `uri`, expects 2xx) |
| Validate an incoming webhook | `Nfe\Webhook::verifySignature($rawBody, $sig, $secret)` |
| Manage state tax registrations (IE) | `$nfe->stateTaxes->*` |
| Cancel a service invoice | `$nfe->serviceInvoices->cancel($companyId, $invoiceId)` (synchronous; returns the DTO) |
| Cancel a product invoice (with reason) | `$nfe->productInvoices->cancel($companyId, $invoiceId, $reason)` |
| Download a PDF/XML | `$nfe->serviceInvoices->downloadPdf($companyId, $id)` (returns `string`) |
| Send invoice by email | `$nfe->serviceInvoices->sendEmail($companyId, $invoiceId)` |
| Issue a correction letter (CC-e) | `$nfe->productInvoices->sendCorrectionLetter($companyId, $id, $text)` |

## Reference Files

Load these for full method signatures and per-resource detail:

- **`references/service-invoices-and-polling.md`** — NFS-e (`serviceInvoices`), `companies` (Account scope, `remove()`, cert read helpers), `legalPeople`/`naturalPeople` (PJ/PF), `webhooks` CRUD, and the full `FlowStatus` terminal/non-terminal table + manual polling recipe.
- **`references/product-invoices-and-taxes.md`** — NF-e/NFC-e (`productInvoices`/`consumerInvoices`: union return, `createWithStateTax`, cursor pagination + required `environment`, CC-e, EPEC, `disable`/`disableRange`), `stateTaxes` (cursor, `{stateTax}` body envelope), `taxCalculation`, `taxCodes`, `transportationInvoices` (CT-e), `inboundProductInvoices`.
- **`references/data-services-and-lookups.md`** — `legalEntityLookup` (CNPJ), `naturalPersonLookup` (CPF), `addresses` (CEP only), `productInvoiceQuery`/`consumerInvoiceQuery` (44-digit access key), the host map and the `dataApiKey` 4-family rule.
- **`references/error-handling-and-patterns.md`** — the `Nfe\Exception\*` hierarchy, `ErrorFactory`, `RetryPolicy`, the static `Nfe\Webhook` (signature validation + `constructEvent`), and the complete manual-polling pattern.
