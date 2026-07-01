<?php

declare(strict_types=1);

namespace Nfe\Response;

/**
 * Represents an HTTP 202 response to an invoice creation request.
 *
 * The API has accepted the request and is processing it asynchronously.
 * Callers should poll the resource at {@see self::location()} (or use the
 * resource's `retrieve()` method with {@see self::invoiceId()}) until the
 * invoice reaches a terminal flow status.
 *
 * A `pollUntilComplete()` convenience helper is deliberately NOT included
 * in v3.0. Loop manually in a worker/CLI context.
 */
interface Pending extends InvoiceResponse
{
    /**
     * The newly-created invoice's identifier, extracted from the Location header.
     */
    public function invoiceId(): string;

    /**
     * The raw Location header value from the 202 response (path, possibly absolute URL).
     */
    public function location(): string;
}
