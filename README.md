# NFE.io PHP SDK

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%5E8.2-787CB5)](composer.json)
[![Status](https://img.shields.io/badge/v3-in--development-orange)](https://github.com/nfe/client-php/tree/v3)

Official PHP SDK for the [NFE.io](https://nfe.io) API. Modern PHP 8.2+, zero runtime dependencies, designed in parity with the [Node.js SDK](https://github.com/nfe/client-nodejs).

> **You are reading the v3 branch.** v3 is a full rewrite and is currently in development. For the stable v2 release, see the [`master` branch](https://github.com/nfe/client-php/tree/master) and the `nfe/nfe` package on Packagist. v2 is frozen and receives no further updates.

## Status

| Branch | Package | Status |
|---|---|---|
| `v3` | `nfe/client-php` | 🚧 **In development** — not yet released |
| `master` | `nfe/nfe` | ❄️ Frozen (v2.5, no further updates) |

## Requirements

- PHP 8.2, 8.3, or 8.4
- ext-curl, ext-json, ext-mbstring

## Installation (once v3 is released)

```bash
composer require nfe/client-php
```

## Quickstart (target API for v3)

```php
use Nfe\Client;
use Nfe\Environment;

$nfe = new Client(
    apiKey: $_ENV['NFE_API_KEY'],
    environment: Environment::Production,
);

// Issue a service invoice (NFS-e)
$result = $nfe->serviceInvoices->create($companyId, [
    'borrower' => [
        'federalTaxNumber' => 12345678901234,
        'name'             => 'Cliente exemplo',
        'email'            => 'cliente@example.com',
    ],
    'cityServiceCode'    => '01234',
    'federalServiceCode' => '01.02',
    'description'        => 'Serviço prestado',
    'servicesAmount'     => 1000.00,
]);

if ($result instanceof Nfe\Response\Pending) {
    // 202 — invoice is being processed asynchronously
    echo "Pending — invoiceId: {$result->invoiceId}\n";
} else {
    // 201 — invoice issued immediately
    echo "Issued: {$result->invoice->id}\n";
}
```

## Resources (parity with the Node.js SDK)

| Property | Endpoint family |
|---|---|
| `$nfe->serviceInvoices` | NFS-e (service invoice) |
| `$nfe->productInvoices` | NF-e (product invoice) |
| `$nfe->consumerInvoices` | NFC-e (consumer invoice) |
| `$nfe->transportationInvoices` | CT-e |
| `$nfe->inboundProductInvoices` | Inbound NF-e |
| `$nfe->productInvoiceQuery` | NF-e query |
| `$nfe->consumerInvoiceQuery` | NFC-e query |
| `$nfe->companies` | Company management |
| `$nfe->legalPeople` | Legal person (PJ) |
| `$nfe->naturalPeople` | Natural person (PF) |
| `$nfe->webhooks` | Webhook configuration |
| `$nfe->addresses` | CEP lookup |
| `$nfe->legalEntityLookup` | CNPJ lookup |
| `$nfe->naturalPersonLookup` | CPF lookup |
| `$nfe->taxCalculation` | Tax calculation |
| `$nfe->taxCodes` | Tax codes (NBS / CNAE) |
| `$nfe->stateTaxes` | State tax registration |

## Webhook signature verification

The SDK ships a static helper aligned with the canonical scheme used by NFE.io (HMAC-SHA1 over `X-Hub-Signature`):

```php
use Nfe\Webhook;
use Nfe\Exception\SignatureVerificationException;

try {
    $event = Webhook::constructEvent(
        payload:   file_get_contents('php://input'),
        sigHeader: $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '',
        secret:    $_ENV['NFE_WEBHOOK_SECRET'],
    );
    // $event is a typed WebhookEvent
} catch (SignatureVerificationException $e) {
    http_response_code(403);
    exit;
}
```

## Polling (manual for v3.0)

For asynchronous invoice issuance (HTTP 202), v3.0 returns a discriminated `Pending | Issued` response. A `pollUntilComplete()` helper will land in a later 3.x release; until then, loop manually in a worker/CLI context:

```php
$result = $nfe->serviceInvoices->create($companyId, $data);

if ($result instanceof Nfe\Response\Pending) {
    do {
        sleep(2);
        $invoice = $nfe->serviceInvoices->retrieve($companyId, $result->invoiceId);
    } while ($invoice->flowStatus === Nfe\Generated\ServiceInvoiceRtcV1\FlowStatus::Pending);
}
```

## Migrating from v2

See [MIGRATION.md](MIGRATION.md) for the full v2 → v3 mapping. There is no backwards compatibility — v3 is a clean rewrite.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

MIT — see [LICENSE](LICENSE).
