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
```

- `create()` returns a **union** — discriminate with `instanceof Nfe\Response\Pending` / `Issued`. HTTP 202 → `Pending` (built from the `Location` header; no body). A 202 **without** `Location` throws `InvalidRequestException`. HTTP 201 → `Issued`; `$issued->resource()` is a `ServiceInvoice`.
- `list()` is **page-style**, `pageIndex` **1-based**: `['pageIndex' => 1, 'pageCount' => 50, 'issuedBegin' => ..., 'issuedEnd' => ...]`. Returns `ListResponse` (`->data` = `ServiceInvoice[]`, `->page`).
- `cancel()` is synchronous and returns the updated `ServiceInvoice` DTO.
- `getStatus()` is unique to service invoices (lightweight status endpoint). Product/consumer have none.
- **No `createAndWait`, no `createBatch`** (deferred post-v3.0).

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

## `$nfe->webhooks` (CRUD — Company-scoped)

```php
list(string $companyId, ?RequestOptions $options = null): ListResponse
create(string $companyId, array $data, ?RequestOptions $options = null): Webhook
retrieve(string $companyId, string $webhookId, ?RequestOptions $options = null): Webhook
update(string $companyId, string $webhookId, array $data, ?RequestOptions $options = null): Webhook
delete(string $companyId, string $webhookId, ?RequestOptions $options = null): void
test(string $companyId, string $webhookId, ?RequestOptions $options = null): array
getAvailableEvents(): array   // hard-coded list, no API call
```

**Signature validation is NOT here** — it lives on the static `Nfe\Webhook` class (see `error-handling-and-patterns.md`).
