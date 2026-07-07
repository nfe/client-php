<?php

declare(strict_types=1);

namespace Nfe\Resource;

use Nfe\Exception\ApiErrorException;
use Nfe\Exception\InvalidRequestException;
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
     * ## Emissão idempotente (retry seguro de POST)
     *
     * A API **não** honra `Idempotency-Key` (reconfirmado em 2026-07-05), então o SDK
     * não retenta POST em 5xx/timeout ambíguo por default — reexecutar poderia emitir
     * uma nota duplicada. Para tornar a emissão retry-safe, envie um `externalId` no
     * `$data` (a API o trata como chave única) e feche o ciclo em caso de falha ambígua:
     *
     * ```php
     * $data['externalId'] = $pedidoId; // estável entre tentativas
     * try {
     *     $result = $nfe->serviceInvoices->create($companyId, $data);
     * } catch (ApiErrorException $e) {
     *     // Retry (manual ou via RequestOptions) pode ser rejeitado como duplicata,
     *     // ou o próprio create pode ter dado 5xx APÓS criar a nota (confirmado ao vivo):
     *     if (ServiceInvoicesResource::isDuplicateExternalId($e) || $e instanceof ServerException) {
     *         // A tentativa anterior pode ter sido processada — recupere a nota já criada.
     *         $result = $nfe->serviceInvoices->findByExternalId($companyId, $pedidoId);
     *     } else {
     *         throw $e;
     *     }
     * }
     * ```
     *
     * Nota: logo após um `202` (pending), a nota pode levar alguns segundos para indexar
     * na rota de busca — se `findByExternalId()` retornar `null` de imediato, reconsulte
     * com um pequeno backoff antes de concluir que a nota não existe.
     *
     * @param array<string, mixed> $data Payload da nota (vide spec NFS-e v1). Inclua
     *        `externalId` para habilitar o ciclo de emissão idempotente acima.
     */
    public function create(
        string $companyId,
        array $data,
        ?RequestOptions $options = null,
    ): ServiceInvoicePending|ServiceInvoiceIssued {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->httpPost("/companies/{$companyId}/serviceinvoices", $data, $options);

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
     *        Aceita: `pageIndex` (**1-based**), `pageCount`, `issuedBegin`,
     *        `issuedEnd`, etc.
     * @return ListResponse<ServiceInvoice>
     */
    public function list(
        string $companyId,
        array $options = [],
        ?RequestOptions $reqOptions = null,
    ): ListResponse {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->httpGet("/companies/{$companyId}/serviceinvoices", $options, $reqOptions);
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
        $response = $this->httpGet("/companies/{$companyId}/serviceinvoices/{$invoiceId}", options: $options);
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
        $response = $this->httpDelete("/companies/{$companyId}/serviceinvoices/{$invoiceId}", $options);
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
        $response = $this->httpPut("/companies/{$companyId}/serviceinvoices/{$invoiceId}/sendemail", options: $options);

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
        $response = $this->httpGet("/companies/{$companyId}/serviceinvoices/{$invoiceId}/status", options: $options);

        return $this->decodeBody($response->body);
    }

    /**
     * Recupera uma NFS-e pelo seu `externalId` (chave definida pelo integrador na emissão).
     *
     * Usa a rota dedicada `GET /v1/companies/{id}/serviceinvoices/external/{externalId}`.
     * Contrato vivo (confirmado em 2026-07-06): no hit a API responde `200` com **envelope
     * de coleção** `{"serviceInvoices":[...]}`; no miss responde `200` com lista vazia
     * (não `404`). O OpenAPI modela um objeto único — tratamos ambos os formatos.
     *
     * Peça central do ciclo de emissão idempotente: após um retry ambíguo rejeitado como
     * duplicata (vide {@see self::isDuplicateExternalId()}), recupera a nota já criada.
     *
     * @return ServiceInvoice|null A nota, ou `null` se nenhuma carrega esse `externalId`.
     */
    public function findByExternalId(
        string $companyId,
        string $externalId,
        ?RequestOptions $options = null,
    ): ?ServiceInvoice {
        $companyId = IdValidator::companyId($companyId);
        if (trim($externalId) === '') {
            throw new InvalidRequestException('externalId must be a non-empty string.');
        }

        $response = $this->httpGet(
            "/companies/{$companyId}/serviceinvoices/external/" . rawurlencode($externalId),
            options: $options,
        );
        $payload = $this->decodeBody($response->body);

        // Contrato real: envelope de coleção no hit, lista vazia no miss.
        if (array_key_exists('serviceInvoices', $payload)) {
            $items = is_array($payload['serviceInvoices']) ? $payload['serviceInvoices'] : [];
            $first = $items[0] ?? null;

            /** @var array<string, mixed>|null $first */
            return is_array($first) ? $this->hydrate(ServiceInvoice::class, $first) : null;
        }

        // Fallback defensivo: corpo com objeto único (o formato que o YAML declara),
        // caso a API seja futuramente alinhada ao próprio spec.
        if (isset($payload['id'])) {
            return $this->hydrate(ServiceInvoice::class, $payload);
        }

        return null;
    }

    /**
     * Reconhece a rejeição de `externalId` duplicado na emissão de NFS-e.
     *
     * A API recusa uma segunda emissão com o mesmo `externalId` retornando
     * `400 "service invoice with external id (…) already exists"` (confirmado ao vivo
     * em 2026-07-05). Como a API não devolve um error code estruturado — só texto livre —
     * o casamento por mensagem vive **apenas aqui**, num único lugar testável.
     *
     * @param ApiErrorException $e Exceção capturada de uma chamada `create()`.
     * @return bool `true` se for a rejeição de duplicata; `false` para qualquer outro 400.
     */
    public static function isDuplicateExternalId(ApiErrorException $e): bool
    {
        if ($e->statusCode !== 400) {
            return false;
        }

        // A mensagem pode chegar no corpo cru ou (futuramente) no campo message.
        $haystack = strtolower(($e->responseBody ?? '') . ' ' . $e->getMessage());

        return str_contains($haystack, 'external id') && str_contains($haystack, 'already exists');
    }
}
