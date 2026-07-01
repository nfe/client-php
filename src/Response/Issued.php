<?php

declare(strict_types=1);

namespace Nfe\Response;

/**
 * Represents a synchronous (HTTP 201) response to an invoice creation request.
 *
 * The invoice has been materialised by the API and is returned in full.
 * Concrete implementations expose a typed `resource()` accessor returning
 * the DTO from {@see \Nfe\Generated} that matches the resource family.
 *
 * @template T of object
 */
interface Issued extends InvoiceResponse
{
    /**
     * @return T The materialised invoice DTO.
     */
    public function resource(): object;
}
