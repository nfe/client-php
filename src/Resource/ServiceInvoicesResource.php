<?php

declare(strict_types=1);

namespace Nfe\Resource;

use Nfe\Http\RequestOptions;
use Nfe\Resource\Dto\ServiceInvoices\ServiceInvoice;
use Nfe\Response\ServiceInvoiceIssued;
use Nfe\Response\ServiceInvoicePending;
use Nfe\Util\IdValidator;
use Nfe\Util\ListResponse;

/**
 * Service invoices (NFS-e) — emission, retrieval, cancellation, email,
 * downloads, and status snapshots.
 *
 * Paridade com `client-nodejs/src/core/resources/service-invoices.ts`.
 * `createAndWait()` e `createBatch()` foram deliberadamente diferidos para
 * uma release pós-v3.0 (vide design.md de c05 e c04).
 */
final class ServiceInvoicesResource extends AbstractResource
{
    protected function apiFamily(): string
    {
        return 'main';
    }

    protected function apiVersion(): string
    {
        return 'v1';
    }

    /**
     * Emite uma NFS-e.
     *
     * Retorna `ServiceInvoicePending` quando a API responde HTTP 202 (processamento
     * assíncrono) ou `ServiceInvoiceIssued` quando responde HTTP 201 (emissão imediata).
     * Use `instanceof` para discriminar.
     *
     * @param array<string, mixed> $data Payload da nota (vide spec NFS-e v1).
     */
    public function create(
        string $companyId,
        array $data,
        ?RequestOptions $options = null,
    ): ServiceInvoicePending|ServiceInvoiceIssued {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->post("/companies/{$companyId}/serviceinvoices", $data, $options);

        $result = $this->handleAsyncResponse(
            response: $response,
            issuedDtoClass: ServiceInvoice::class,
            issuedFactory: fn(ServiceInvoice $invoice): ServiceInvoiceIssued => new ServiceInvoiceIssued($invoice),
            pendingFactory: fn(string $invoiceId, string $location): ServiceInvoicePending => new ServiceInvoicePending($invoiceId, $location),
        );

        /** @var ServiceInvoicePending|ServiceInvoiceIssued $result */
        return $result;
    }

    /**
     * Lista as NFS-e de uma empresa com paginação.
     *
     * @param array<string, scalar|array<int, scalar>> $options
     *        Aceita: `pageIndex`, `pageCount`, `issuedBegin`, `issuedEnd`, etc.
     * @return ListResponse<ServiceInvoice>
     */
    public function list(
        string $companyId,
        array $options = [],
        ?RequestOptions $reqOptions = null,
    ): ListResponse {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->get("/companies/{$companyId}/serviceinvoices", $options, $reqOptions);
        $payload = $this->decodeBody($response->body);

        return $this->hydrateList(ServiceInvoice::class, $payload, 'serviceInvoices');
    }

    /**
     * Recupera uma NFS-e específica.
     */
    public function retrieve(
        string $companyId,
        string $invoiceId,
        ?RequestOptions $options = null,
    ): ServiceInvoice {
        $companyId = IdValidator::companyId($companyId);
        $invoiceId = IdValidator::invoiceId($invoiceId);
        $response = $this->get("/companies/{$companyId}/serviceinvoices/{$invoiceId}", options: $options);
        $payload = $this->decodeBody($response->body);

        return $this->hydrate(ServiceInvoice::class, $payload);
    }

    /**
     * Cancela uma NFS-e. O retorno é o DTO atualizado da nota cancelada (síncrono — confirmado contra Node SDK).
     */
    public function cancel(
        string $companyId,
        string $invoiceId,
        ?RequestOptions $options = null,
    ): ServiceInvoice {
        $companyId = IdValidator::companyId($companyId);
        $invoiceId = IdValidator::invoiceId($invoiceId);
        $response = $this->delete("/companies/{$companyId}/serviceinvoices/{$invoiceId}", $options);
        $payload = $this->decodeBody($response->body);

        return $this->hydrate(ServiceInvoice::class, $payload);
    }

    /**
     * Envia a NFS-e por email para o tomador (borrower).
     *
     * @return array<string, mixed> Payload da resposta da API (típico: `{sent: bool, message?: string}`).
     */
    public function sendEmail(
        string $companyId,
        string $invoiceId,
        ?RequestOptions $options = null,
    ): array {
        $companyId = IdValidator::companyId($companyId);
        $invoiceId = IdValidator::invoiceId($invoiceId);
        $response = $this->put("/companies/{$companyId}/serviceinvoices/{$invoiceId}/sendemail", options: $options);

        return $this->decodeBody($response->body);
    }

    /**
     * Baixa o PDF da NFS-e como bytes crus.
     */
    public function downloadPdf(
        string $companyId,
        string $invoiceId,
        ?RequestOptions $options = null,
    ): string {
        $companyId = IdValidator::companyId($companyId);
        $invoiceId = IdValidator::invoiceId($invoiceId);

        return $this->download("/companies/{$companyId}/serviceinvoices/{$invoiceId}/pdf", options: $options);
    }

    /**
     * Baixa o XML da NFS-e como bytes crus.
     */
    public function downloadXml(
        string $companyId,
        string $invoiceId,
        ?RequestOptions $options = null,
    ): string {
        $companyId = IdValidator::companyId($companyId);
        $invoiceId = IdValidator::invoiceId($invoiceId);

        return $this->download("/companies/{$companyId}/serviceinvoices/{$invoiceId}/xml", options: $options);
    }

    /**
     * Snapshot de status (flowStatus + flowMessage no mínimo).
     *
     * @return array<string, mixed>
     */
    public function getStatus(
        string $companyId,
        string $invoiceId,
        ?RequestOptions $options = null,
    ): array {
        $companyId = IdValidator::companyId($companyId);
        $invoiceId = IdValidator::invoiceId($invoiceId);
        $response = $this->get("/companies/{$companyId}/serviceinvoices/{$invoiceId}/status", options: $options);

        return $this->decodeBody($response->body);
    }
}
