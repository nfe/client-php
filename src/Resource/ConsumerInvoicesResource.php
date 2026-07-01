<?php

declare(strict_types=1);

namespace Nfe\Resource;

use Nfe\Http\RequestOptions;
use Nfe\Resource\Dto\ConsumerInvoices\ConsumerInvoice;
use Nfe\Response\ConsumerInvoiceIssued;
use Nfe\Response\ConsumerInvoicePending;
use Nfe\Util\IdValidator;
use Nfe\Util\ListResponse;

/**
 * Consumer invoices (NFC-e — Nota Fiscal de Consumidor Eletrônica).
 *
 * Hosted at `https://api.nfse.io` under v2. Implements the emission +
 * lifecycle paths exposed by `openapi/nf-consumidor-v2.yaml`.
 *
 * **Paridade Node**: o Node SDK v3.2.0 deliberadamente NÃO expõe NFC-e
 * emission (apenas consulta via `consumerInvoiceQuery`). O PHP SDK estende
 * além dessa paridade porque a API NFE.io oferece o recurso desde v2 —
 * útil para integrações de PoS, e-commerce, etc.
 *
 * Diferenças vs `ProductInvoicesResource`:
 * - NFC-e NÃO tem carta de correção (CC-e) — apenas NF-e produto tem
 * - NFC-e NÃO tem EPEC (Evento Prévio de Emissão em Contingência)
 * - Inutilização (`disableRange`) é só coletiva, não por invoice individual
 */
final class ConsumerInvoicesResource extends AbstractResource
{
    protected function apiFamily(): string
    {
        return 'consumer-invoices';
    }

    protected function apiVersion(): string
    {
        return 'v2';
    }

    /**
     * Emite uma NFC-e.
     *
     * Retorna `ConsumerInvoicePending` para 202 (assíncrono) ou
     * `ConsumerInvoiceIssued` para 201 (síncrono). Discriminar com `instanceof`.
     *
     * @param array<string, mixed> $data
     */
    public function create(
        string $companyId,
        array $data,
        ?RequestOptions $options = null,
    ): ConsumerInvoicePending|ConsumerInvoiceIssued {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->httpPost("/companies/{$companyId}/consumerinvoices", $data, $options);

        /** @var ConsumerInvoicePending|ConsumerInvoiceIssued $result */
        $result = $this->handleAsyncResponse(
            $response,
            ConsumerInvoice::class,
            fn(ConsumerInvoice $invoice): ConsumerInvoiceIssued => new ConsumerInvoiceIssued($invoice),
            fn(string $id, string $loc): ConsumerInvoicePending => new ConsumerInvoicePending($id, $loc),
        );
        return $result;
    }

    /**
     * Variante de emissão escopada a uma inscrição estadual específica.
     *
     * @param array<string, mixed> $data
     */
    public function createWithStateTax(
        string $companyId,
        string $stateTaxId,
        array $data,
        ?RequestOptions $options = null,
    ): ConsumerInvoicePending|ConsumerInvoiceIssued {
        $companyId = IdValidator::companyId($companyId);
        $stateTaxId = IdValidator::stateTaxId($stateTaxId);
        $response = $this->httpPost(
            "/companies/{$companyId}/statetaxes/{$stateTaxId}/consumerinvoices",
            $data,
            $options,
        );

        /** @var ConsumerInvoicePending|ConsumerInvoiceIssued $result */
        $result = $this->handleAsyncResponse(
            $response,
            ConsumerInvoice::class,
            fn(ConsumerInvoice $invoice): ConsumerInvoiceIssued => new ConsumerInvoiceIssued($invoice),
            fn(string $id, string $loc): ConsumerInvoicePending => new ConsumerInvoicePending($id, $loc),
        );
        return $result;
    }

    /**
     * Lista NFC-e emitidas para uma empresa.
     *
     * @param array<string, scalar|array<int, scalar>> $options
     * @return ListResponse<ConsumerInvoice>
     */
    public function list(
        string $companyId,
        array $options = [],
        ?RequestOptions $reqOptions = null,
    ): ListResponse {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->httpGet("/companies/{$companyId}/consumerinvoices", $options, $reqOptions);
        $payload = $this->decodeBody($response->body);

        return $this->hydrateList(ConsumerInvoice::class, $payload, 'consumerInvoices');
    }

    public function retrieve(
        string $companyId,
        string $invoiceId,
        ?RequestOptions $options = null,
    ): ConsumerInvoice {
        $companyId = IdValidator::companyId($companyId);
        $invoiceId = IdValidator::invoiceId($invoiceId);
        $response = $this->httpGet("/companies/{$companyId}/consumerinvoices/{$invoiceId}", options: $options);

        return $this->hydrate(ConsumerInvoice::class, $this->decodeBody($response->body));
    }

    public function cancel(
        string $companyId,
        string $invoiceId,
        ?RequestOptions $options = null,
    ): ConsumerInvoice {
        $companyId = IdValidator::companyId($companyId);
        $invoiceId = IdValidator::invoiceId($invoiceId);
        $response = $this->httpDelete("/companies/{$companyId}/consumerinvoices/{$invoiceId}", $options);

        return $this->hydrate(ConsumerInvoice::class, $this->decodeBody($response->body));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listItems(
        string $companyId,
        string $invoiceId,
        ?RequestOptions $options = null,
    ): array {
        $companyId = IdValidator::companyId($companyId);
        $invoiceId = IdValidator::invoiceId($invoiceId);
        $response = $this->httpGet(
            "/companies/{$companyId}/consumerinvoices/{$invoiceId}/items",
            options: $options,
        );
        $payload = $this->decodeBody($response->body);

        return is_array($payload['items'] ?? null) ? $payload['items'] : (array_is_list($payload) ? $payload : []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listEvents(
        string $companyId,
        string $invoiceId,
        ?RequestOptions $options = null,
    ): array {
        $companyId = IdValidator::companyId($companyId);
        $invoiceId = IdValidator::invoiceId($invoiceId);
        $response = $this->httpGet(
            "/companies/{$companyId}/consumerinvoices/{$invoiceId}/events",
            options: $options,
        );
        $payload = $this->decodeBody($response->body);

        return is_array($payload['events'] ?? null) ? $payload['events'] : (array_is_list($payload) ? $payload : []);
    }

    public function downloadPdf(string $companyId, string $invoiceId, ?RequestOptions $options = null): string
    {
        $companyId = IdValidator::companyId($companyId);
        $invoiceId = IdValidator::invoiceId($invoiceId);

        return $this->download(
            "/companies/{$companyId}/consumerinvoices/{$invoiceId}/pdf",
            options: $options,
        );
    }

    public function downloadXml(string $companyId, string $invoiceId, ?RequestOptions $options = null): string
    {
        $companyId = IdValidator::companyId($companyId);
        $invoiceId = IdValidator::invoiceId($invoiceId);

        return $this->download(
            "/companies/{$companyId}/consumerinvoices/{$invoiceId}/xml",
            options: $options,
        );
    }

    public function downloadRejectionXml(string $companyId, string $invoiceId, ?RequestOptions $options = null): string
    {
        $companyId = IdValidator::companyId($companyId);
        $invoiceId = IdValidator::invoiceId($invoiceId);

        return $this->download(
            "/companies/{$companyId}/consumerinvoices/{$invoiceId}/xml/rejection",
            options: $options,
        );
    }

    /**
     * Inutilização coletiva de uma faixa de NFC-e.
     *
     * NFC-e não suporta inutilização por invoice individual (diferente do NF-e
     * produto que tem `disable(invoiceId)` E `disableRange()`). Apenas coletiva.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function disableRange(
        string $companyId,
        array $data,
        ?RequestOptions $options = null,
    ): array {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->httpPost(
            "/companies/{$companyId}/consumerinvoices/disablement",
            $data,
            $options,
        );

        return $this->decodeBody($response->body);
    }
}
