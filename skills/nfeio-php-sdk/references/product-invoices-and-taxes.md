# Product/Consumer Invoices, State Taxes, Tax Calculation, CT-e & Inbound

Resources on `api.nfse.io/v2` (except queries — see `data-services-and-lookups.md`). Signatures verbatim from the SDK.

## `$nfe->productInvoices` (NF-e — Company-scoped)

```php
create(string $companyId, array $data, ?RequestOptions $options = null): ProductInvoicePending|ProductInvoiceIssued
createWithStateTax(string $companyId, string $stateTaxId, array $data, ?RequestOptions $options = null): ProductInvoicePending|ProductInvoiceIssued
list(string $companyId, array $options, ?RequestOptions $reqOptions = null): ListResponse   // $options REQUIRED
retrieve(string $companyId, string $invoiceId, ?RequestOptions $options = null): ProductInvoice
cancel(string $companyId, string $invoiceId, ?string $reason = null, ?RequestOptions $options = null): ProductInvoice
listItems(string $companyId, string $invoiceId, ?RequestOptions $options = null): array   // raw arrays
listEvents(string $companyId, string $invoiceId, ?RequestOptions $options = null): array
downloadPdf(string $companyId, string $invoiceId, ?RequestOptions $options = null): string
downloadXml(string $companyId, string $invoiceId, ?RequestOptions $options = null): string
downloadRejectionXml(string $companyId, string $invoiceId, ?RequestOptions $options = null): string
downloadEpecXml(string $companyId, string $invoiceId, ?RequestOptions $options = null): string
sendCorrectionLetter(string $companyId, string $invoiceId, string $correction, ?RequestOptions $options = null): array
downloadCorrectionLetterPdf(string $companyId, string $invoiceId, ?RequestOptions $options = null): string
downloadCorrectionLetterXml(string $companyId, string $invoiceId, ?RequestOptions $options = null): string
disable(string $companyId, string $invoiceId, array $data, ?RequestOptions $options = null): array          // single: PUT .../{id}/disable
disableRange(string $companyId, array $data, ?RequestOptions $options = null): array                        // range: PUT .../productinvoices/disable
```

- **`list()` requires `environment`** and its `$options` arg is mandatory (cursor pagination): `['environment' => 'Production', 'limit' => 25, 'startingAfter' => $id, 'q' => ...]`.
- `create()` is async (202 → `Pending`); completion is **webhook-driven** — prefer webhooks over polling.
- `cancel()` takes an optional `$reason` (appended as `?reason=`).
- `sendCorrectionLetter()` throws `InvalidRequestException` locally if the text is empty/whitespace.
- `listItems`/`listEvents` return raw associative arrays, not DTOs.

## `$nfe->consumerInvoices` (NFC-e — Company-scoped)

```php
create(string $companyId, array $data, ?RequestOptions $options = null): ConsumerInvoicePending|ConsumerInvoiceIssued
createWithStateTax(string $companyId, string $stateTaxId, array $data, ?RequestOptions $options = null): ConsumerInvoicePending|ConsumerInvoiceIssued
list(string $companyId, array $options = [], ?RequestOptions $reqOptions = null): ListResponse
retrieve(string $companyId, string $invoiceId, ?RequestOptions $options = null): ConsumerInvoice
cancel(string $companyId, string $invoiceId, ?RequestOptions $options = null): ConsumerInvoice
listItems(string $companyId, string $invoiceId, ?RequestOptions $options = null): array
listEvents(string $companyId, string $invoiceId, ?RequestOptions $options = null): array
downloadPdf(string $companyId, string $invoiceId, ?RequestOptions $options = null): string
downloadXml(string $companyId, string $invoiceId, ?RequestOptions $options = null): string
downloadRejectionXml(string $companyId, string $invoiceId, ?RequestOptions $options = null): string
disableRange(string $companyId, array $data, ?RequestOptions $options = null): array   // POST .../consumerinvoices/disablement
```

- NFC-e emission is webhook-driven. No `getStatus()`, no correction letter, no EPEC.
- `disableRange()` here POSTs to `.../disablement` (different verb/path from product's `disable`). No per-invoice disable.

## `$nfe->stateTaxes` (Inscrição Estadual / IE — Company-scoped, prerequisite for NF-e)

```php
list(string $companyId, ?array $opts = null, ?RequestOptions $options = null): ListResponse   // cursor pagination
create(string $companyId, array $data, ?RequestOptions $options = null): NfeStateTax
retrieve(string $companyId, string $stateTaxId, ?RequestOptions $options = null): NfeStateTax
update(string $companyId, string $stateTaxId, array $data, ?RequestOptions $options = null): NfeStateTax
delete(string $companyId, string $stateTaxId, ?RequestOptions $options = null): void
```

- `create`/`update` wrap the body as `{ "stateTax": <data> }` (Node parity); the response is hydrated directly (no envelope unwrap).

## `$nfe->taxCalculation` (Tenant-scoped)

```php
calculate(string $tenantId, array $request, ?RequestOptions $options = null): array   // raw array response
```

- Request/response are untyped arrays. `POST /tax-rules/{tenantId}/engine/calculate`. Throws `InvalidRequestException` on empty `$tenantId` or empty `items`.
- The expected `$request` shape is documented by the generated PHP class `Nfe\Generated\CalculoImpostosV1\CalculateRequest` (a DTO you can mirror, not a required type).
- **Does not use `dataApiKey`** — always the main `apiKey`.

## `$nfe->taxCodes` (Global reference data)

```php
listOperationCodes(array $opts = [], ?RequestOptions $options = null): TaxCodePaginatedResponse
listAcquisitionPurposes(array $opts = [], ?RequestOptions $options = null): TaxCodePaginatedResponse
listIssuerTaxProfiles(array $opts = [], ?RequestOptions $options = null): TaxCodePaginatedResponse
listRecipientTaxProfiles(array $opts = [], ?RequestOptions $options = null): TaxCodePaginatedResponse
```

- Page-style pagination, `pageIndex` **1-based** (default 1), `pageCount` default 50, **max 50**. Response carries `currentPage`, `totalPages`, `totalCount`, `items`.

## `$nfe->transportationInvoices` (CT-e inbound — Company-scoped)

```php
enable(string $companyId, array $data, ?RequestOptions $options = null): InboundSettings
disable(string $companyId, ?RequestOptions $options = null): InboundSettings
getSettings(string $companyId, ?RequestOptions $options = null): InboundSettings
retrieve(string $companyId, string $accessKey, ?RequestOptions $options = null): array
downloadXml(string $companyId, string $accessKey, ?RequestOptions $options = null): string
getEvent(string $companyId, string $accessKey, string $eventKey, ?RequestOptions $options = null): array
downloadEventXml(string $companyId, string $accessKey, string $eventKey, ?RequestOptions $options = null): string
```

- **Does not use `dataApiKey`** — always the main `apiKey`.

## `$nfe->inboundProductInvoices` (Inbound NF-e / DFe distribution — Company-scoped)

```php
enableAutoFetch(string $companyId, array $data, ?RequestOptions $options = null): InboundSettings
disableAutoFetch(string $companyId, ?RequestOptions $options = null): InboundSettings
getSettings(string $companyId, ?RequestOptions $options = null): InboundSettings
getDetails(string $companyId, string $accessKey, ?RequestOptions $options = null): array
getProductInvoiceDetails(string $companyId, string $accessKey, ?RequestOptions $options = null): array
getEventDetails(string $companyId, string $accessKey, string $eventKey, ?RequestOptions $options = null): array
getProductInvoiceEventDetails(string $companyId, string $accessKey, string $eventKey, ?RequestOptions $options = null): array
getXml(string $companyId, string $accessKey, ?RequestOptions $options = null): string
getEventXml(string $companyId, string $accessKey, string $eventKey, ?RequestOptions $options = null): string
getPdf(string $companyId, string $accessKey, ?RequestOptions $options = null): string
getJson(string $companyId, string $accessKey, ?RequestOptions $options = null): array
manifest(string $companyId, string $accessKey, string $manifestType, array $data = [], ?RequestOptions $options = null): array
reprocessWebhook(string $companyId, string $accessKey, ?RequestOptions $options = null): array
```

- `getXml`/`getEventXml`/`getPdf` return raw byte `string`. **Does not use `dataApiKey`** — always the main `apiKey`.
