# Nfe\Generated

> **AUTO-GENERATED CODE — DO NOT EDIT MANUALLY**

Everything below this directory is produced by `scripts/generate.php` from the
OpenAPI specifications under [`openapi/`](../../openapi/). The directory is
recreated wholesale on every run; manual edits will be lost.

## Regenerating

```bash
composer generate
```

## Verifying sync

```bash
composer generate:check
```

CI runs this on every push to the `v3` branch and fails the build if the
committed `src/Generated/` tree diverges from what the current specs would
produce.

## Layout

One subdirectory per OpenAPI spec file, named after the kebab-to-PascalCase
mapping of the filename:

| Spec file | Generated namespace |
|---|---|
| `openapi/service-invoice-rtc-v1.yaml` | `Nfe\Generated\ServiceInvoiceRtcV1` |
| `openapi/product-invoice-rtc-v1.yaml` | `Nfe\Generated\ProductInvoiceRtcV1` |
| `openapi/consumer-invoice-v3.yaml`    | `Nfe\Generated\ConsumerInvoiceV3` |
| `openapi/consulta-cnpj-v3.yaml`       | `Nfe\Generated\ConsultaCnpjV3` |
| ... | ... |

## When to regenerate

Whenever any file in `openapi/` is modified. The CI guard exists to make this
forced — open a PR that touches `openapi/*.yaml` without rerunning the
generator and CI will fail with a diff of what would change.
