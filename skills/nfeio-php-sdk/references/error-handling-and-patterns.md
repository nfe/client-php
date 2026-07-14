# Error Handling, Retry, Webhooks & Polling

Cross-cutting patterns. Signatures verbatim from the SDK.

## Exception hierarchy (`Nfe\Exception\…`)

```
RuntimeException
└── ApiErrorException            (base; statusCode, responseBody, responseHeaders, errorCode)
    ├── AuthenticationException      (401)
    ├── AuthorizationException       (403 — key/plan lacks the product)
    ├── InvalidRequestException      (400/422 + local validation, thrown synchronously)
    ├── NotFoundException            (404)
    ├── RateLimitException           (429)
    ├── ServerException              (5xx)
    ├── ApiConnectionException       (network/cURL failure; adds ->failurePhase, ->curlErrno)
    └── SignatureVerificationException (webhook signature mismatch)
```

- There are **no** `isXxx()` type-guard functions — use `instanceof`. Catch specific subclasses first, then `ApiErrorException` as the catch-all.
- Base-class properties on every API error: `->statusCode`, `->responseBody`, `->responseHeaders`, `->errorCode`, plus the usual `getMessage()`.
- `Nfe\Exception\ErrorFactory::fromResponse(Response $response)` maps an HTTP response to the correct exception (used internally by resources).

```php
use Nfe\Exception\{ApiErrorException, AuthenticationException, InvalidRequestException, RateLimitException};

try {
    $result = $nfe->serviceInvoices->create($companyId, $data);
} catch (AuthenticationException $e) {
    // 401 — check API key
} catch (InvalidRequestException $e) {
    // 400/422 or local validation
} catch (RateLimitException $e) {
    // 429 — back off
} catch (ApiErrorException $e) {
    error_log("[{$e->errorCode}] HTTP {$e->statusCode}: {$e->getMessage()}");
    error_log($e->responseBody);
}
```

## Retry (`Nfe\Http\RetryPolicy`)

- Default (via `Config`): `maxRetries = 3`, `baseDelay = 1.0s`, `maxDelay = 30.0s`, `jitter = 0.3`. The `RetryPolicy` signature is unchanged since v3.0.
- Disable with `Nfe\Http\RetryPolicy::none()`.
- Transports return a `Response` for HTTP 4xx/5xx (the resource layer decides what to throw); only genuine connection failures raise `ApiConnectionException`. Retries apply to transient conditions, not to ordinary 4xx client errors — 429 (rate limiting) is the sole 4xx that is retried (see the table below).
- Retry is **method-aware** (`Nfe\Http\RetryingTransport`, since v3.2.0): retrying an idempotent request is always safe, but re-sending a POST can duplicate an NFS-e, so POST is retried far more narrowly.

| Method | 429 | 5xx | network `ConnectionNotEstablished` | network `RequestMaybeSent` / unclassified |
|---|---|---|---|---|
| GET / PUT / DELETE / HEAD / OPTIONS | retry | retry | retry | retry |
| POST | retry | **no** | retry | **no** |
| POST + `Idempotency-Key` header | retry | retry | retry | retry |

- **Behavior change vs v3.1 and earlier:** POST no longer retries on 5xx (it used to). A 5xx can mean the server already issued the note, so re-POSTing would duplicate it — this is a safety fix. 429 is still retried for POST (a rate-limited request is rejected before processing).
- A POST carrying an `Idempotency-Key` request header is treated as idempotent (retried like a GET). Forward-compat only: **the API does not honor the header yet**, so it merely unlocks the retry — it does not make the server dedupe. For safe emission today, send an `externalId` and reconcile after an ambiguous failure (see `service-invoices-and-polling.md`).
- `Retry-After` response header is honored (integer seconds only; HTTP-date form is not).

**Failure phase (`Nfe\Http\FailurePhase`).** Network failures carry a phase on `ApiConnectionException->failurePhase` (plus the raw `->curlErrno`) so the retry layer can tell a safe-to-resend POST from an unsafe one:

- `ConnectionNotEstablished` — the request provably never reached the server (DNS, TCP connect, TLS handshake; cURL errnos 5/6/7/35, or PSR-18 `NetworkExceptionInterface`). Safe to retry any method, including POST.
- `RequestMaybeSent` — the server may have received/processed it (e.g. read timeout after send; errno 28). Unsafe for POST. Unclassified failures (`failurePhase === null`) are treated the same, conservatively.

**Per-request retry override (`RequestOptions->retry`, since v3.2.0).** Every resource method accepts a `RequestOptions` whose optional `retry` (a `RetryPolicy`) overrides the client-level policy for that single call — in both directions. This removes the old two-client workaround (one retrying client for reads, one non-retrying for writes).

```php
use Nfe\Client;
use Nfe\Config;
use Nfe\Http\RequestOptions;
use Nfe\Http\RetryPolicy;

$nfe = new Client(config: new Config(
    apiKey: $key,
    retry: new RetryPolicy(maxRetries: 5, baseDelay: 0.5, maxDelay: 20.0, jitter: 0.3),
));

// Disable retry for one call only (the rest of the client keeps retrying):
$nfe->serviceInvoices->create($companyId, $data, new RequestOptions(retry: RetryPolicy::none()));
```

## Webhook signature validation — the static `Nfe\Webhook`

Signature validation is **not** on `$nfe->webhooks` (that's CRUD only). It's a standalone static class:

```php
Nfe\Webhook::verifySignature(string $payload, string $signature, string $secret, string $algo = 'sha1'): bool
Nfe\Webhook::constructEvent(string $payload, string $sigHeader, string $secret, string $algo = 'sha1'): Nfe\WebhookEvent
```

- Pass the **raw request body bytes** (`file_get_contents('php://input')`), never a re-encoded array — re-serialising changes the bytes and breaks the HMAC.
- `verifySignature()` accepts both `sha1=<hex>` (prefixed) and bare hex, defaults to HMAC-SHA1, and refuses an algorithm downgrade (a `sha256=` header against `$algo='sha1'`).
- `constructEvent()` verifies the signature, unwraps the v2 `{ action, payload }` envelope, and returns a typed `Nfe\WebhookEvent` — it **throws** `SignatureVerificationException` on mismatch (unlike `verifySignature()`, which returns `false`).

```php
use Nfe\Webhook;
use Nfe\Exception\SignatureVerificationException;

$raw = file_get_contents('php://input');
$sig = $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '';

try {
    $event = Nfe\Webhook::constructEvent($raw, $sig, $_ENV['NFE_WEBHOOK_SECRET']);
    // $event->... — typed WebhookEvent
} catch (SignatureVerificationException $e) {
    http_response_code(400);
}
```

## Manual polling (there is no createAndWait)

```php
use Nfe\Response\Pending;
use Nfe\Util\FlowStatus;

$r = $nfe->serviceInvoices->create($companyId, $data);

if ($r instanceof Pending) {
    $id       = $r->invoiceId();
    $deadline = time() + 300;              // budget your own timeout (municipalities can take minutes)
    $delay    = 1;
    do {
        sleep($delay);
        $delay = min($delay * 2, 10);      // your own backoff
        $flow  = $nfe->serviceInvoices->getStatus($companyId, $id)['flowStatus'] ?? '';
    } while (!FlowStatus::isTerminal($flow) && time() < $deadline);

    if ($flow === 'Issued') {
        $invoice = $nfe->serviceInvoices->retrieve($companyId, $id);
    } else {
        // 'IssueFailed' | 'Cancelled' | 'CancelFailed' — or timed out (still non-terminal)
    }
}
```

- `getStatus()` exists only on `serviceInvoices`. For `productInvoices`/`consumerInvoices`, poll via `retrieve()` and read the DTO's flow field — but those are **webhook-driven**, so prefer a webhook over polling.
- `FlowStatus::isTerminal()` is true for failure states too — always inspect the concrete status.
