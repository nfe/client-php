# Service Invoices, Companies, People, Webhooks & Polling

Resources on `api.nfe.io/v1`. All method signatures below are verbatim from the SDK.

## `$nfe->serviceInvoices` (NFS-e — Company-scoped)

```php
create(string $companyId, array $data, ?RequestOptions $options = null): ServiceInvoicePending|ServiceInvoiceIssued
list(string $companyId, array $options = [], ?RequestOptions $reqOptions = null): ListResponse
retrieve(string $companyId, string $invoiceId, ?RequestOptions $options = null): ServiceInvoice
cancel(string $companyId, string $invoiceId, ?RequestOptions $options = null): ServiceInvoice   // synchronous
sendEmail(string $companyId, string $invoiceId, ?RequestOptions $options = null): array
downloadPdf(string $companyId, string $invoiceId, ?RequestOptions $options = null): string       // raw bytes
downloadXml(string $companyId, string $invoiceId, ?RequestOptions $options = null): string
getStatus(string $companyId, string $invoiceId, ?RequestOptions $options = null): array           // {flowStatus, flowMessage, ...}
findByExternalId(string $companyId, string $externalId, ?RequestOptions $options = null): ?ServiceInvoice   // dedicated /external route (v3.2.0)
static isDuplicateExternalId(ApiErrorException $e): bool   // matches the 400 duplicate-externalId rejection (v3.2.0)
```

- `create()` returns a **union** — discriminate with `instanceof Nfe\Response\Pending` / `Issued`. HTTP 202 → `Pending` (built from the `Location` header; no body). A 202 **without** `Location` throws `InvalidRequestException`. HTTP 201 → `Issued`; `$issued->resource()` is a `ServiceInvoice`.
- `list()` is **page-style**, `pageIndex` **1-based**: `['pageIndex' => 1, 'pageCount' => 50, 'issuedBegin' => ..., 'issuedEnd' => ...]`. Returns `ListResponse` (`->data` = `ServiceInvoice[]`, `->page`).
- `cancel()` is synchronous and returns the updated `ServiceInvoice` DTO.
- `getStatus()` is unique to service invoices (lightweight status endpoint). Product/consumer have none.
- **No `createAndWait`, no `createBatch`** (deferred post-v3.0).

### ServiceInvoice DTO (v3.3.0)

Typed fields: `id`, `status`, `flowStatus`, `flowMessage`, `environment`, `rpsNumber`, `rpsSerialNumber`, `issuedOn`, `createdOn`, `modifiedOn`, `cancelledOn`, `servicesAmount`, `externalId`, plus (since v3.3.0) **`number`** (the fiscal invoice number, `?int`), **`checkCode`** (verification code), `description`, `cityServiceCode`, `baseTaxAmount`, `issRate`, `issTaxAmount`, `amountNet`, and **`borrower`** (a typed `Borrower` DTO).

- **`raw` is populated** (since v3.3.0): every hydrated `ServiceInvoice` carries the full decoded payload in `->raw` — any field not typed above (e.g. `provider`, `paidAmount`, `rpsStatus`, the withheld-tax breakdown) is reachable as `$invoice->raw['field']`. This holds per item in `list()` results, and SDK-wide for every DTO that declares a `raw` param (webhooks, lookups, product/consumer…).
- **`Borrower`** fields: `name`, `federalTaxNumber` (**`int|string|null`** — the borrower may be CPF or CNPJ; hydration is strict-typed, so the union tolerates both wire forms), `email`, `phoneNumber`, `id`, `parentId`, `address` (`?array`), `raw`.
- **`provider` is NOT typed** — read it via `$invoice->raw['provider']`.
- **`totalAmount` is a deprecated phantom** — the live API never returns it (always `null`). Use `servicesAmount`/`amountNet` or `raw`. Do NOT generate code that reads `->totalAmount`.
- The DTO is pinned to `openapi/nf-servico-v1.yaml` by an alignment test (anchored by path — the spec's `operationId` collides between routes).

### Idempotent emission via externalId (v3.2.0)

`externalId` is a **unique key** on emission: a 2nd `create()` with the same `externalId` is rejected with `400 "service invoice with external id (…) already exists"`. Since POST is no longer retried on 5xx (see `error-handling-and-patterns.md`), the safe-emission cycle is:

```php
use Nfe\Exception\ApiErrorException;
use Nfe\Exception\ServerException;
use Nfe\Resource\ServiceInvoicesResource;

$data['externalId'] = $orderId; // stable across attempts
try {
    $result = $nfe->serviceInvoices->create($companyId, $data);
} catch (ApiErrorException $e) {
    // duplicate rejection (a retry that was already processed) OR ambiguous 5xx
    // (the API can return 500 and STILL create the note — live-confirmed):
    if (ServiceInvoicesResource::isDuplicateExternalId($e) || $e instanceof ServerException) {
        $invoice = $nfe->serviceInvoices->findByExternalId($companyId, $orderId);
        // null → the note truly was not created; safe to re-emit
    } else {
        throw $e;
    }
}
```

- `findByExternalId()` uses the dedicated route `GET /v1/companies/{id}/serviceinvoices/external/{externalId}`. Wire contract: hit = 200 with a **collection envelope** `{"serviceInvoices":[...]}`; miss = **200 with an empty list** (not 404) → returns `null`.
- **Indexing lag:** right after a 202-pending create, `findByExternalId()` may return `null` for a few seconds; re-query with a small backoff before concluding the note does not exist. The `400 "already exists"` rejection is immediate.

### Create payload (typical NFS-e)

```php
$nfe->serviceInvoices->create($companyId, [
    'cityServiceCode' => '2690',
    'description'     => 'IT Consulting Services',
    'servicesAmount'  => 1500.00,
    'borrower'        => [
        'federalTaxNumber' => 11444555000149,
        'name'  => 'Client Company',
        'email' => 'client@example.com',
        'address' => [ /* ... */ ],
    ],
]);
```

## FlowStatus (`Nfe\Util\FlowStatus`)

```php
FlowStatus::isTerminal(string $status): bool
```

- **Terminal** (`FlowStatus::TERMINAL`): `Issued`, `IssueFailed`, `Cancelled`, `CancelFailed`.
- **Non-terminal**: `WaitingSend`, `WaitingReturn`, `WaitingDownload`, `WaitingCalculateTaxes`, `WaitingDefineRpsNumber`, `WaitingSendCancel`, `PullFromCityHall`.
- `isTerminal()` is true for both success and failure — inspect the concrete status.

### Manual polling recipe

```php
use Nfe\Response\Pending;
use Nfe\Util\FlowStatus;

$r = $nfe->serviceInvoices->create($companyId, $data);
if ($r instanceof Pending) {
    $id = $r->invoiceId();
    do {
        sleep(3);
        $flow = $nfe->serviceInvoices->getStatus($companyId, $id)['flowStatus'] ?? '';
    } while (!FlowStatus::isTerminal($flow));
    $invoice = $flow === 'Issued' ? $nfe->serviceInvoices->retrieve($companyId, $id) : null;
}
```

## `$nfe->companies` (Account-scoped — no `$companyId`)

```php
create(array $data, ?RequestOptions $options = null): Company
list(array $options = [], ?RequestOptions $reqOptions = null): ListResponse
listAll(?RequestOptions $options = null): array                 // walks all pages client-side
retrieve(string $companyId, ?RequestOptions $options = null): Company
update(string $companyId, array $data, ?RequestOptions $options = null): Company
remove(string $companyId, ?RequestOptions $options = null): array   // {deleted, id} — NOT delete()
findByTaxNumber(string|int $taxNumber, ?RequestOptions $options = null): ?Company   // client-side scan; null if absent
findByName(string $name, ?RequestOptions $options = null): array
getCertificateStatus(string $companyId, int $expiringSoonThreshold = 30, ?RequestOptions $options = null): CertificateStatus
checkCertificateExpiration(string $companyId, int $thresholdDays = 30, ?RequestOptions $options = null): array
getCompaniesWithCertificates(?RequestOptions $options = null): array
getCompaniesWithExpiringCertificates(int $thresholdDays = 30, ?RequestOptions $options = null): array
```

- **Certificate WRITE is not implemented** (no `uploadCertificate`/`validateCertificate` — transport is JSON-only). Only the read helpers above.
- `CertificateStatus` computes `daysUntilExpiration`/`isExpiringSoon` client-side from `expiresOn`.

## `$nfe->legalPeople` (PJ) and `$nfe->naturalPeople` (PF) — Company-scoped

```php
list(string $companyId, ?RequestOptions $options = null): ListResponse
create(string $companyId, array $data, ?RequestOptions $options = null): LegalPerson|NaturalPerson
retrieve(string $companyId, string $personId, ?RequestOptions $options = null): LegalPerson|NaturalPerson
update(string $companyId, string $personId, array $data, ?RequestOptions $options = null): LegalPerson|NaturalPerson
delete(string $companyId, string $personId, ?RequestOptions $options = null): void
createBatch(string $companyId, array $batch, ?RequestOptions $options = null): array   // SEQUENTIAL
findByTaxNumber(string $companyId, string|int $taxNumber, ?RequestOptions $options = null): ?LegalPerson|?NaturalPerson
```

- `deletion` is `delete(): void` here (vs `companies->remove()`).
- **`federalTaxNumber` DTO type**: `LegalPerson` = `?int`, `NaturalPerson` = `?string`. No create-side validation/coercion.
- `createBatch` runs sequentially (no parallelism).

## `$nfe->webhooks` (CRUD — Account-scoped, `/v2/webhooks`)

Webhooks are registered per **account** — every company of the account fires to the same targets; use `filters` to select events.

```php
listAccountWebhooks(?RequestOptions $options = null): ListResponse            // unwraps {webHooks: [...]}
createAccountWebhook(array $data, ?RequestOptions $options = null): AccountWebhook
retrieveAccountWebhook(string $webhookId, ?RequestOptions $options = null): AccountWebhook
updateAccountWebhook(string $webhookId, array $data, ?RequestOptions $options = null): AccountWebhook
deleteAccountWebhook(string $webhookId, ?RequestOptions $options = null): void
deleteAllAccountWebhooks(?RequestOptions $options = null): void  // DESTRUCTIVE: removes ALL account webhooks
pingAccountWebhook(string $webhookId, ?RequestOptions $options = null): void  // PUT /v2/webhooks/{id}/pings
fetchEventTypes(?RequestOptions $options = null): array          // live list<string> of event ids
```

`AccountWebhook` DTO fields: `id`, `uri`, `contentType` (string on the wire, e.g. `"json"`), `secret` (32–64 chars; echoed on create, omitted on reads), `filters`, `insecureSsl`, `headers`, `properties`, `status` (`"Active"`/`"Inactive"`), `createdOn`, `modifiedOn`, `raw`.

- The SDK wraps create/update bodies in the mandatory `{"webHook": {...}}` envelope and unwraps enveloped responses — pass the plain `$data` array, not a pre-wrapped one.
- **Create pings the `uri`** with an HTTP POST and requires a 2xx — a dead endpoint fails the create.
- **Update is a full PUT replacement**: omitted fields reset to defaults; omitting `status` deactivates the webhook. Build the body from a fresh `retrieveAccountWebhook()`.
- Event ids follow `resource.event_action` (`service_invoice.issued_successfully`, `product_invoice.*`, `consumer_invoice.*`) — use `fetchEventTypes()` for the live list.
- Deprecated (route 404s on the live API; kept for BC only): company-scoped `list`/`create`/`retrieve`/`update`/`delete`/`test($companyId, …)` and the hard-coded `getAvailableEvents()`.

**Signature validation is NOT here** — it lives on the static `Nfe\Webhook` class (see `error-handling-and-patterns.md`).
