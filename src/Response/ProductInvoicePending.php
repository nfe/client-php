<?php

declare(strict_types=1);

namespace Nfe\Response;

/**
 * Concrete `Pending` for {@see \Nfe\Resource\ProductInvoicesResource::create()}.
 */
final readonly class ProductInvoicePending implements Pending
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
