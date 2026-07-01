<?php

declare(strict_types=1);

namespace Nfe\Response;

/**
 * Concrete `Pending` for {@see \Nfe\Resource\ConsumerInvoicesResource::create()}.
 *
 * Issued when the API responds HTTP 202 with a `Location` header pointing
 * to the eventual NFC-e resource.
 */
final readonly class ConsumerInvoicePending implements Pending
{
    public function __construct(
        private string $invoiceId,
        private string $location,
    ) {}

    public function invoiceId(): string
    {
        return $this->invoiceId;
    }

    public function location(): string
    {
        return $this->location;
    }
}
