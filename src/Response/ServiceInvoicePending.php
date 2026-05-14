<?php

declare(strict_types=1);

namespace Nfe\Response;

/**
 * Concrete `Pending` for {@see \Nfe\Resource\ServiceInvoicesResource::create()}.
 *
 * Issued when the API responds HTTP 202 with a `Location` header pointing
 * to the eventual invoice resource. Callers should poll
 * `$nfe->serviceInvoices->retrieve($companyId, $invoiceId)` until
 * {@see \Nfe\Util\FlowStatus::isTerminal()} returns true.
 */
final readonly class ServiceInvoicePending implements Pending
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
