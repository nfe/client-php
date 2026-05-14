<?php

declare(strict_types=1);

namespace Nfe\Resource;

use Nfe\Http\RequestOptions;
use Nfe\Resource\Dto\ProductInvoices\ProductInvoice;
use Nfe\Response\ProductInvoiceIssued;
use Nfe\Response\ProductInvoicePending;
use Nfe\Util\IdValidator;
use Nfe\Util\ListResponse;

/**
 * Product invoices (NF-e) — emission, listing, retrieval, cancellation, events,
 * correction letters, downloads (PDF/XML/Rejection/EPEC), and inutilization.
 *
 * Hosted at `https://api.nfse.io` under v2 (per `Config::baseUrlForApi('product-invoices')`).
 *
 * Paridade 1:1 com `client-nodejs/src/core/resources/product-invoices.ts`.
 */
final class ProductInvoicesResource extends AbstractResource
{
    protected function apiFamily(): string
    {
        return 'product-invoices';
    }

    protected function apiVersion(): string
    {
        return 'v2';
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(
        string $companyId,
        array $data,
        ?RequestOptions $options = null,
    ): ProductInvoicePending|ProductInvoiceIssued {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->post("/companies/{$companyId}/productinvoices", $data, $options);

        /** @var ProductInvoicePending|ProductInvoiceIssued $result */
        $result = $this->handleAsyncResponse(
            $response,
            ProductInvoice::class,
            fn(ProductInvoice $invoice): ProductInvoiceIssued => new ProductInvoiceIssued($invoice),
            fn(string $id, string $loc): ProductInvoicePending => new ProductInvoicePending($id, $loc),
        );
        return $result;
    }

    /**
     * Variante de criação que escopa a emissão a uma inscrição estadual específica.
     *
     * @param array<string, mixed> $data
     */
    public function createWithStateTax(
        string $companyId,
        string $stateTaxId,
        array $data,
        ?RequestOptions $options = null,
    ): ProductInvoicePending|ProductInvoiceIssued {
        $companyId = IdValidator::companyId($companyId);
        $stateTaxId = IdValidator::stateTaxId($stateTaxId);
        $response = $this->post(
            "/companies/{$companyId}/statetaxes/{$stateTaxId}/productinvoices",
            $data,
            $options,
        );

        /** @var ProductInvoicePending|ProductInvoiceIssued $result */
        $result = $this->handleAsyncResponse(
            $response,
            ProductInvoice::class,
            fn(ProductInvoice $invoice): ProductInvoiceIssued => new ProductInvoiceIssued($invoice),
            fn(string $id, string $loc): ProductInvoicePending => new ProductInvoicePending($id, $loc),
        );
        return $result;
    }

    /**
     * Lista NF-e com paginação cursor-style (Node usa `startingAfter`/`endingBefore`/`limit`).
     *
     * @param array<string, scalar|array<int, scalar>> $options
     *        `environment` é obrigatório; demais opcionais: `startingAfter`, `endingBefore`, `limit`, `q`.
     * @return ListResponse<ProductInvoice>
     */
    public function list(
        string $companyId,
        array $options,
        ?RequestOptions $reqOptions = null,
    ): ListResponse {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->get("/companies/{$companyId}/productinvoices", $options, $reqOptions);
        $payload = $this->decodeBody($response->body);

        return $this->hydrateList(ProductInvoice::class, $payload, 'productInvoices');
    }

    public function retrieve(
        string $companyId,
        string $invoiceId,
        ?RequestOptions $options = null,
    ): ProductInvoice {
        $companyId = IdValidator::companyId($companyId);
        $invoiceId = IdValidator::invoiceId($invoiceId);
        $response = $this->get("/companies/{$companyId}/productinvoices/{$invoiceId}", options: $options);

        return $this->hydrate(ProductInvoice::class, $this->decodeBody($response->body));
    }

    public function cancel(
        string $companyId,
        string $invoiceId,
        ?string $reason = null,
        ?RequestOptions $options = null,
    ): ProductInvoice {
        $companyId = IdValidator::companyId($companyId);
        $invoiceId = IdValidator::invoiceId($invoiceId);
        $query = $reason !== null ? ['reason' => $reason] : [];
        $response = $this->send_delete("/companies/{$companyId}/productinvoices/{$invoiceId}", $query, $options);

        return $this->hydrate(ProductInvoice::class, $this->decodeBody($response->body));
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
        $response = $this->get("/companies/{$companyId}/productinvoices/{$invoiceId}/items", options: $options);
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
        $response = $this->get("/companies/{$companyId}/productinvoices/{$invoiceId}/events", options: $options);
        $payload = $this->decodeBody($response->body);

        return is_array($payload['events'] ?? null) ? $payload['events'] : (array_is_list($payload) ? $payload : []);
    }

    public function downloadPdf(string $companyId, string $invoiceId, ?RequestOptions $options = null): string
    {
        $companyId = IdValidator::companyId($companyId);
        $invoiceId = IdValidator::invoiceId($invoiceId);

        return $this->download("/companies/{$companyId}/productinvoices/{$invoiceId}/pdf", options: $options);
    }

    public function downloadXml(string $companyId, string $invoiceId, ?RequestOptions $options = null): string
    {
        $companyId = IdValidator::companyId($companyId);
        $invoiceId = IdValidator::invoiceId($invoiceId);

        return $this->download("/companies/{$companyId}/productinvoices/{$invoiceId}/xml", options: $options);
    }

    public function downloadRejectionXml(string $companyId, string $invoiceId, ?RequestOptions $options = null): string
    {
        $companyId = IdValidator::companyId($companyId);
        $invoiceId = IdValidator::invoiceId($invoiceId);

        return $this->download("/companies/{$companyId}/productinvoices/{$invoiceId}/xml/rejection", options: $options);
    }

    public function downloadEpecXml(string $companyId, string $invoiceId, ?RequestOptions $options = null): string
    {
        $companyId = IdValidator::companyId($companyId);
        $invoiceId = IdValidator::invoiceId($invoiceId);

        return $this->download("/companies/{$companyId}/productinvoices/{$invoiceId}/xml/epec", options: $options);
    }

    /**
     * Envia uma carta de correção (CC-e) para uma NF-e já emitida.
     *
     * @return array<string, mixed> Payload da carta de correção.
     */
    public function sendCorrectionLetter(
        string $companyId,
        string $invoiceId,
        string $correction,
        ?RequestOptions $options = null,
    ): array {
        $companyId = IdValidator::companyId($companyId);
        $invoiceId = IdValidator::invoiceId($invoiceId);
        if (trim($correction) === '') {
            throw new \Nfe\Exception\InvalidRequestException('Texto da carta de correção é obrigatório.');
        }
        $response = $this->put(
            "/companies/{$companyId}/productinvoices/{$invoiceId}/correctionletter",
            ['correction' => $correction],
            $options,
        );

        return $this->decodeBody($response->body);
    }

    public function downloadCorrectionLetterPdf(string $companyId, string $invoiceId, ?RequestOptions $options = null): string
    {
        $companyId = IdValidator::companyId($companyId);
        $invoiceId = IdValidator::invoiceId($invoiceId);

        return $this->download("/companies/{$companyId}/productinvoices/{$invoiceId}/correctionletter/pdf", options: $options);
    }

    public function downloadCorrectionLetterXml(string $companyId, string $invoiceId, ?RequestOptions $options = null): string
    {
        $companyId = IdValidator::companyId($companyId);
        $invoiceId = IdValidator::invoiceId($invoiceId);

        return $this->download("/companies/{$companyId}/productinvoices/{$invoiceId}/correctionletter/xml", options: $options);
    }

    /**
     * Inutiliza uma NF-e específica.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function disable(
        string $companyId,
        string $invoiceId,
        array $data,
        ?RequestOptions $options = null,
    ): array {
        $companyId = IdValidator::companyId($companyId);
        $invoiceId = IdValidator::invoiceId($invoiceId);
        $response = $this->put("/companies/{$companyId}/productinvoices/{$invoiceId}/disable", $data, $options);

        return $this->decodeBody($response->body);
    }

    /**
     * Inutiliza uma faixa de numeração.
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
        $response = $this->put("/companies/{$companyId}/productinvoices/disable", $data, $options);

        return $this->decodeBody($response->body);
    }

    /**
     * @param array<string, scalar|array<int, scalar>> $query
     */
    private function send_delete(string $path, array $query, ?RequestOptions $options): \Nfe\Http\Response
    {
        // Helper local — DELETE com query string. AbstractResource::delete() não aceita query;
        // emulamos via GET-with-DELETE-method seria errado; usamos build manual.
        if ($query === []) {
            return $this->delete($path, $options);
        }
        // Compose path with query for the DELETE-with-reason case.
        $qs = http_build_query($query);
        return $this->delete($path . '?' . $qs, $options);
    }
}
