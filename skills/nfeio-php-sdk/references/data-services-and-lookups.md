# Data Services & Lookups (CNPJ / CPF / CEP / Query by access key)

Read-only lookup/query resources across dedicated hosts. Signatures verbatim from the SDK.

## Host map & the `dataApiKey` rule

| Family | Host | Uses `dataApiKey`? |
|---|---|---|
| `addresses` | `address.api.nfe.io/v2` | **yes** (falls back to `apiKey`) |
| `legalEntityLookup` (`legal-entity`) | `legalentity.api.nfe.io` | **yes** |
| `naturalPersonLookup` (`natural-person`) | `naturalperson.api.nfe.io` | **yes** |
| `productInvoiceQuery` / `consumerInvoiceQuery` (`nfe-query`) | `nfe.api.nfe.io` | **yes** |
| everything else (incl. `taxCalculation`, `transportationInvoices`, `inboundProductInvoices`, `taxCodes`) | — | **no** (always `apiKey`) |

Only those four families read `dataApiKey`; when it's null they fall back to `apiKey`. A 403 on these despite a valid main key usually means the key's plan lacks the data-services product — pass a provisioned `dataApiKey`.

## `$nfe->legalEntityLookup` (CNPJ — Global)

```php
getBasicInfo(string $cnpj, ?array $opts = null, ?RequestOptions $options = null): LegalEntityResponse
getStateTaxInfo(string $state, string $cnpj, ?RequestOptions $options = null): LegalEntityResponse
getStateTaxForInvoice(string $state, string $cnpj, ?RequestOptions $options = null): LegalEntityResponse
getSuggestedStateTaxForInvoice(string $state, string $cnpj, ?RequestOptions $options = null): LegalEntityResponse
```

- `getBasicInfo` `$opts` forwards query params (e.g. `['updateAddress' => false, 'updateCityCode' => true]`).
- `$state` is normalised to uppercase; an unknown UF throws `InvalidRequestException`.
- `LegalEntityResponse->legalEntity` is the unwrapped inner `legalEntity` value (a PHP **array**, or null); `->raw` is the full decoded payload.

## `$nfe->naturalPersonLookup` (CPF — Global)

```php
getStatus(string $cpf, \DateTimeImmutable|string $birthDate, ?RequestOptions $options = null): NaturalPersonStatus
```

- `$birthDate` accepts an ISO `YYYY-MM-DD` string or a `\DateTimeImmutable` (normalised via `Nfe\Util\DateNormalizer::toIsoDate`). Malformed/out-of-range dates throw `InvalidRequestException`.

## `$nfe->addresses` (CEP — Global)

```php
lookupByPostalCode(string $cep, ?RequestOptions $options = null): AddressLookupResponse
```

- **CEP is the only live endpoint.** `search()` and `lookupByTerm()` were removed (their endpoints return 404).
- Accepts CEP with or without hyphen; normalised to 8 digits via `IdValidator::cep` (invalid length throws `InvalidRequestException` synchronously).
- The API returns `{ "address": { … } }`; the SDK unwraps it into `AddressLookupResponse->addresses` (a 1-element list). Read fields directly: `$r->addresses[0]['street']`. `->raw` holds the original payload.

## `$nfe->productInvoiceQuery` (NF-e query by access key — Global)

```php
retrieve(string $accessKey, ?RequestOptions $options = null): ProductInvoiceDetails
downloadPdf(string $accessKey, ?RequestOptions $options = null): string   // raw DANFE bytes
downloadXml(string $accessKey, ?RequestOptions $options = null): string
listEvents(string $accessKey, ?RequestOptions $options = null): array
```

## `$nfe->consumerInvoiceQuery` (CFe-SAT / NFC-e coupon query — Global)

```php
retrieve(string $accessKey, ?RequestOptions $options = null): TaxCoupon
downloadXml(string $accessKey, ?RequestOptions $options = null): string
```

- Access keys are 44 numeric digits. These query resources are distinct from the emission resources `productInvoices`/`consumerInvoices`.
