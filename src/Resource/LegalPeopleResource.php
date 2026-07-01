<?php

declare(strict_types=1);

namespace Nfe\Resource;

use Nfe\Http\RequestOptions;
use Nfe\Resource\Dto\LegalPeople\LegalPerson;
use Nfe\Util\IdValidator;
use Nfe\Util\ListResponse;

/**
 * Legal persons (pessoas jurídicas / PJ) registered under a company.
 *
 * Paridade com `client-nodejs/src/core/resources/legal-people.ts`.
 */
final class LegalPeopleResource extends AbstractResource
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
     * @return ListResponse<LegalPerson>
     */
    public function list(string $companyId, ?RequestOptions $options = null): ListResponse
    {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->httpGet("/companies/{$companyId}/legalpeople", options: $options);
        $payload = $this->decodeBody($response->body);

        return $this->hydrateList(LegalPerson::class, $payload, 'legalPeople');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(string $companyId, array $data, ?RequestOptions $options = null): LegalPerson
    {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->httpPost("/companies/{$companyId}/legalpeople", $data, $options);

        return $this->hydrate(LegalPerson::class, $this->unwrap($this->decodeBody($response->body), 'legalPeople'));
    }

    public function retrieve(string $companyId, string $legalPersonId, ?RequestOptions $options = null): LegalPerson
    {
        $companyId = IdValidator::companyId($companyId);
        $legalPersonId = IdValidator::invoiceId($legalPersonId);
        $response = $this->httpGet("/companies/{$companyId}/legalpeople/{$legalPersonId}", options: $options);

        return $this->hydrate(LegalPerson::class, $this->unwrap($this->decodeBody($response->body), 'legalPeople'));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(
        string $companyId,
        string $legalPersonId,
        array $data,
        ?RequestOptions $options = null,
    ): LegalPerson {
        $companyId = IdValidator::companyId($companyId);
        $legalPersonId = IdValidator::invoiceId($legalPersonId);
        $response = $this->httpPut("/companies/{$companyId}/legalpeople/{$legalPersonId}", $data, $options);

        return $this->hydrate(LegalPerson::class, $this->unwrap($this->decodeBody($response->body), 'legalPeople'));
    }

    public function delete(string $companyId, string $legalPersonId, ?RequestOptions $options = null): void
    {
        $companyId = IdValidator::companyId($companyId);
        $legalPersonId = IdValidator::invoiceId($legalPersonId);
        parent::httpDelete("/companies/{$companyId}/legalpeople/{$legalPersonId}", $options);
    }

    /**
     * Cria múltiplos legal people em batch.
     *
     * **Diferente do Node SDK que usa `Promise.all`, esta implementação é
     * sequencial** (PHP CLI síncrono). Para batches grandes considere
     * paralelizar via processos externos ou aguardar release com adapter
     * para Fiber/Amp.
     *
     * @param list<array<string, mixed>> $batch
     * @return list<LegalPerson>
     */
    public function createBatch(string $companyId, array $batch, ?RequestOptions $options = null): array
    {
        $companyId = IdValidator::companyId($companyId);
        $created = [];
        foreach ($batch as $data) {
            $created[] = $this->create($companyId, $data, $options);
        }
        return $created;
    }

    /**
     * @param string|int $cnpj Federal tax number (CNPJ); formatted or digits-only.
     */
    public function findByTaxNumber(
        string $companyId,
        string|int $cnpj,
        ?RequestOptions $options = null,
    ): ?LegalPerson {
        $needle = (string) $cnpj;
        // Strip non-digits for comparison
        $needleDigits = preg_replace('/\D+/', '', $needle) ?? $needle;
        foreach ($this->list($companyId, $options)->data as $person) {
            if ((string) $person->federalTaxNumber === $needleDigits) {
                return $person;
            }
        }
        return null;
    }
}
