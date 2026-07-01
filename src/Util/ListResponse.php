<?php

declare(strict_types=1);

namespace Nfe\Util;

/**
 * Container for paginated listing responses.
 *
 * @template T
 */
final readonly class ListResponse
{
    /**
     * @param list<T> $data
     */
    public function __construct(
        public array $data,
        public ListPage $page,
    ) {}
}
