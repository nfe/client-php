<?php

declare(strict_types=1);

namespace Nfe\Response;

/**
 * Marker interface implemented by both {@see Pending} and {@see Issued}.
 *
 * Resource methods that issue invoices return either a `Pending`
 * (async — HTTP 202) or an `Issued` (sync — HTTP 201). Callers
 * discriminate with `instanceof`.
 */
interface InvoiceResponse {}
