<?php

declare(strict_types=1);

namespace Nfe\Response;

use Nfe\Resource\Dto\ServiceInvoices\ServiceInvoice;

/**
 * Concrete `Issued` for {@see \Nfe\Resource\ServiceInvoicesResource::create()}.
 *
 * Issued when the API responds HTTP 201 with the materialised invoice body.
 *
 * @implements Issued<ServiceInvoice>
 */
final readonly class ServiceInvoiceIssued implements Issued
{
    public function __construct(
        private ServiceInvoice $invoice,
    ) {}

    public function resource(): ServiceInvoice
    {
        return $this->invoice;
    }
}
