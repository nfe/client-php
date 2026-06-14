<?php

declare(strict_types=1);

namespace Nfe\Response;

use Nfe\Resource\Dto\ConsumerInvoices\ConsumerInvoice;

/**
 * Concrete `Issued` for {@see \Nfe\Resource\ConsumerInvoicesResource::create()}.
 *
 * @implements Issued<ConsumerInvoice>
 */
final readonly class ConsumerInvoiceIssued implements Issued
{
    public function __construct(
        private ConsumerInvoice $invoice,
    ) {}

    public function resource(): ConsumerInvoice
    {
        return $this->invoice;
    }
}
