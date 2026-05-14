# Migrating from v2 to v3

> v3 is a full rewrite. There is no backwards compatibility shim, no alias, and no
> drop-in upgrade path. This document maps the most common v2 patterns to their v3
> equivalents.

## Composer package name

| v2 | v3 |
|---|---|
| `composer require nfe/nfe` | `composer require nfe/client-php` |

`nfe/nfe` and `nfe/client-php` are distinct Packagist packages and can coexist in a project temporarily during migration. Once your code is on v3, drop `nfe/nfe`.

## PHP version

| v2 | v3 |
|---|---|
| PHP 5.4+ | PHP 8.2, 8.3, or 8.4 |

## Namespace and class names

v2 used a flat `NFe_` prefix without PHP namespaces. v3 uses the `Nfe\` namespace with PSR-4 autoloading.

| v2 | v3 |
|---|---|
| `NFe_io` | `Nfe\Client` (instance, not static) |
| `NFe_Company` | `Nfe\Client::companies` (resource) |
| `NFe_ServiceInvoice` | `Nfe\Client::serviceInvoices` (resource) |
| `NFe_LegalPerson` | `Nfe\Client::legalPeople` (resource) |
| `NFe_NaturalPerson` | `Nfe\Client::naturalPeople` (resource) |
| `NFe_Webhook` | `Nfe\Client::webhooks` (resource); see also `Nfe\Webhook` helper |
| `NFe_APIRequest` | `Nfe\Http\CurlTransport` (internal) |

## Configuration

```php
// v2
NFe_io::setApiKey('your-key');
NFe_io::setBaseURI('https://api.nfe.io/v1');

// v3
$nfe = new Nfe\Client(
    apiKey: 'your-key',
    environment: Nfe\Environment::Production,
);
```

The base URL is no longer configured globally; v3 routes per-resource to the correct NFE.io host automatically (api.nfe.io, api.nfse.io, etc.).

## Static calls become instance calls

```php
// v2 (static / active-record-ish)
$company = NFe_Company::create(['name' => '...']);
$invoice = NFe_ServiceInvoice::create([...]);

// v3 (instance / Stripe-style)
$company = $nfe->companies->create(['name' => '...']);
$invoice = $nfe->serviceInvoices->create($companyId, [...]);
```

## Error handling

v2 returned objects with an `errors` field and used a generic `NFeException`.
v3 raises typed exceptions you can catch specifically.

```php
// v2
$response = NFe_ServiceInvoice::create($attrs);
if (isset($response->errors)) { /* handle */ }

// v3
try {
    $invoice = $nfe->serviceInvoices->create($companyId, $data);
} catch (Nfe\Exception\AuthenticationException $e) {
    // 401
} catch (Nfe\Exception\InvalidRequestException $e) {
    // 400 — $e->responseBody, $e->errorCode
} catch (Nfe\Exception\RateLimitException $e) {
    // 429 — SDK has already retried; surface to caller
} catch (Nfe\Exception\ApiErrorException $e) {
    // anything else from the API
}
```

## Asynchronous (202) responses

v2 did not have a typed shape for the 202 + Location pattern that NFE.io uses for
asynchronous invoice issuance. v3 returns a discriminated union:

```php
$result = $nfe->serviceInvoices->create($companyId, $data);

if ($result instanceof Nfe\Response\Pending) {
    // The API accepted the request (HTTP 202) and is processing.
    // $result->invoiceId, $result->location
} else {
    // HTTP 201 — invoice was issued immediately.
    // $result->invoice is the typed ServiceInvoice DTO
}
```

A `pollUntilComplete()` convenience helper is **not** included in v3.0; loop manually with `retrieve()` in CLI/worker contexts. See README for an example.

## Webhook signature verification

The v2 SDK did not provide a webhook signature helper. Integrators rolled their own
(see, e.g., `nfeio-whmcs-modulo`).

v3 ships `Nfe\Webhook`:

```php
use Nfe\Webhook;

$event = Webhook::constructEvent(
    payload:   file_get_contents('php://input'),
    sigHeader: $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '',
    secret:    $webhookSecret,
);
```

Algorithm: HMAC-SHA1 over the raw request body, hex-encoded, with `sha1=<hex>` prefix in the `X-Hub-Signature` header. Confirmed canonical scheme as of 2026-05-13.

## Generated types

v2 had ad-hoc `NFe_Object`-derived classes per entity. v3 generates DTOs from the
OpenAPI specs in `openapi/` into `src/Generated/`. Never edit these by hand.

```php
// Type the result of retrieve()
$invoice = $nfe->serviceInvoices->retrieve($companyId, $invoiceId);
// $invoice is Nfe\Generated\ServiceInvoiceRtcV1\ServiceInvoice
```

## Polling helper, idempotency keys, and other deferred features

- `pollUntilComplete()` — deferred to a future 3.x release.
- `Idempotency-Key` header — the NFE.io API does not support it today (confirmed 2026-05-13). The SDK does not expose a slot for it. When the API adds support, an additive minor release will add the option.

## Detailed migration examples

> _To be filled in during the c08-release-tooling change with at least: a vanilla
> example, a Laravel example, and the WHMCS module pattern (`nfeio-whmcs-modulo`)._
