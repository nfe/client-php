<?php

declare(strict_types=1);

namespace Nfe\Resource\Dto\Addresses;

/**
 * Address lookup response (CEP / search / term).
 *
 * The actual NFE.io API returns either a single `address` object or an
 * `addresses` array, depending on the endpoint. We expose `addresses` as a
 * normalised list for uniform consumption.
 */
final readonly class AddressLookupResponse
{
    /**
     * @param list<array<string, mixed>> $addresses
     * @param array<string, mixed>|null $raw
     */
    public function __construct(
        public array $addresses = [],
        public ?array $raw = null,
    ) {}
}
