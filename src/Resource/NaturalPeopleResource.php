<?php

declare(strict_types=1);

namespace Nfe\Resource;

use Nfe\Http\RequestOptions;
use Nfe\Resource\Dto\NaturalPeople\NaturalPerson;
use Nfe\Util\IdValidator;
use Nfe\Util\ListResponse;

/**
 * Natural persons (pessoas físicas / PF) registered under a company.
 *
 * Paridade com `client-nodejs/src/core/resources/natural-people.ts`.
 */
final class NaturalPeopleResource extends AbstractResource
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
     * @return ListResponse<NaturalPerson>
     */
    public function list(string $companyId, ?RequestOptions $options = null): ListResponse
    {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->httpGet("/companies/{$companyId}/naturalpeople", options: $options);
        $payload = $this->decodeBody($response->body);

        return $this->hydrateList(NaturalPerson::class, $payload, 'naturalPeople');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(string $companyId, array $data, ?RequestOptions $options = null): NaturalPerson
    {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->httpPost("/companies/{$companyId}/naturalpeople", $data, $options);

        return $this->hydrate(NaturalPerson::class, $this->unwrap($this->decodeBody($response->body), 'naturalPeople'));
    }

    public function retrieve(
        string $companyId,
        string $naturalPersonId,
        ?RequestOptions $options = null,
    ): NaturalPerson {
        $companyId = IdValidator::companyId($companyId);
        $naturalPersonId = IdValidator::invoiceId($naturalPersonId);
        $response = $this->httpGet("/companies/{$companyId}/naturalpeople/{$naturalPersonId}", options: $options);

        return $this->hydrate(NaturalPerson::class, $this->unwrap($this->decodeBody($response->body), 'naturalPeople'));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(
        string $companyId,
        string $naturalPersonId,
        array $data,
        ?RequestOptions $options = null,
    ): NaturalPerson {
        $companyId = IdValidator::companyId($companyId);
        $naturalPersonId = IdValidator::invoiceId($naturalPersonId);
        $response = $this->httpPut("/companies/{$companyId}/naturalpeople/{$naturalPersonId}", $data, $options);

        return $this->hydrate(NaturalPerson::class, $this->unwrap($this->decodeBody($response->body), 'naturalPeople'));
    }

    public function delete(string $companyId, string $naturalPersonId, ?RequestOptions $options = null): void
    {
        $companyId = IdValidator::companyId($companyId);
        $naturalPersonId = IdValidator::invoiceId($naturalPersonId);
        parent::httpDelete("/companies/{$companyId}/naturalpeople/{$naturalPersonId}", $options);
    }

    /**
     * Cria múltiplos natural people em batch (sequencial).
     *
     * @param list<array<string, mixed>> $batch
     * @return list<NaturalPerson>
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
     * @param string|int $cpf CPF; formatted or digits-only.
     */
    public function findByTaxNumber(
        string $companyId,
        string|int $cpf,
        ?RequestOptions $options = null,
    ): ?NaturalPerson {
        $needle = (string) $cpf;
        $needleDigits = preg_replace('/\D+/', '', $needle) ?? $needle;
        foreach ($this->list($companyId, $options)->data as $person) {
            if ((string) $person->federalTaxNumber === $needleDigits) {
                return $person;
            }
        }
        return null;
    }
}
