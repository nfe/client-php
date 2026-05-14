<?php

declare(strict_types=1);

namespace Nfe\Util;

/**
 * Pagination metadata returned with every listing response.
 *
 * The NFE.io API uses two different pagination styles depending on the endpoint:
 *
 *   - **Page-style** (`pageIndex` / `pageCount`): used by companies, service invoices,
 *     tax codes. `pageIndex` is exposed 0-based in PHP for consistency, even when
 *     the underlying API uses 1-based indexing (the resource handles the translation).
 *
 *   - **Cursor-style** (`startingAfter` / `endingBefore` / `limit`): used by product
 *     invoices, state taxes.
 *
 * Each resource populates only the half that applies to its endpoint; the others
 * remain `null`. Callers don't need to discriminate — the value object simply exposes
 * both shapes as optional properties.
 */
final readonly class ListPage
{
    public function __construct(
        public ?int $pageIndex = null,
        public ?int $pageCount = null,
        public ?string $startingAfter = null,
        public ?string $endingBefore = null,
        public ?int $total = null,
    ) {}
}
