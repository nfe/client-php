<?php

declare(strict_types=1);

namespace Nfe\Resource\Dto\TaxCodes;

/**
 * Paginated response for tax codes listings.
 *
 * Shape mirrors Node SDK's `TaxCodePaginatedResponse` (types.ts:2214-2225).
 * Pagination is page-style 1-based (`currentPage`, `totalPages`, `totalCount`).
 */
final readonly class TaxCodePaginatedResponse
{
    /**
     * @param list<array<string, mixed>> $items Each item is a TaxCode record (typed loosely
     *        since the four tax-code endpoints have slightly different shapes).
     */
    public function __construct(
        public array $items = [],
        public ?int $currentPage = null,
        public ?int $totalPages = null,
        public ?int $totalCount = null,
    ) {}
}
