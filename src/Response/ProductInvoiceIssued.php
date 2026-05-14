<?php

declare(strict_types=1);

namespace Nfe\Response;

use Nfe\Resource\Dto\ProductInvoices\ProductInvoice;

/**
 * Concrete `Issued` for {@see \Nfe\Resource\ProductInvoicesResource::create()}.
 *
 * @implements Issued<ProductInvoice>
 */
final readonly class ProductInvoiceIssued implements Issued
{
    public function __construct(
        private ProductInvoice $invoice,
    ) {}

    public function resource(): ProductInvoice
    {
        return $this->invoice;
    }
}
