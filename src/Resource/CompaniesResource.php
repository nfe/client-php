<?php

declare(strict_types=1);

namespace Nfe\Resource;

use DateTimeImmutable;
use Exception;
use Nfe\Http\RequestOptions;
use Nfe\Resource\Dto\Companies\CertificateStatus;
use Nfe\Resource\Dto\Companies\Company;
use Nfe\Util\IdValidator;
use Nfe\Util\ListResponse;
use Throwable;

/**
 * Companies — issuers of invoices on NFE.io.
 *
 * Covers CRUD, listAll, finders, and read-only certificate operations.
 * Certificate upload/replace/validate are deferred to a follow-up change
 * (requires multipart support in the transport).
 *
 * Paridade com `client-nodejs/src/core/resources/companies.ts`.
 */
final class CompaniesResource extends AbstractResource
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
     * @param array<string, mixed> $data
     */
    public function create(array $data, ?RequestOptions $options = null): Company
    {
        $response = $this->httpPost('/companies', $data, $options);
        $payload = $this->decodeBody($response->body);

        return $this->hydrate(Company::class, $this->unwrap($payload, 'companies'));
    }

    /**
     * @param array<string, scalar|array<int, scalar>> $options
     * @return ListResponse<Company>
     */
    public function list(array $options = [], ?RequestOptions $reqOptions = null): ListResponse
    {
        $response = $this->httpGet('/companies', $options, $reqOptions);
        $payload = $this->decodeBody($response->body);

        return $this->hydrateList(Company::class, $payload, 'companies');
    }

    /**
     * Auto-paginates over `list()` until exhausted.
     *
     * **Caveat**: loads all companies into memory at once. For accounts with
     * thousands of companies, prefer manual pagination.
     *
     * @return list<Company>
     */
    public function listAll(?RequestOptions $options = null): array
    {
        $all = [];
        $pageIndex = 0;
        $pageSize = 100;
        do {
            $page = $this->list(['pageIndex' => $pageIndex, 'pageCount' => $pageSize], $options);
            foreach ($page->data as $company) {
                $all[] = $company;
            }
            $count = count($page->data);
            $pageIndex++;
        } while ($count === $pageSize);

        return $all;
    }

    public function retrieve(string $companyId, ?RequestOptions $options = null): Company
    {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->httpGet("/companies/{$companyId}", options: $options);

        return $this->hydrate(Company::class, $this->unwrap($this->decodeBody($response->body), 'companies'));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $companyId, array $data, ?RequestOptions $options = null): Company
    {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->httpPut("/companies/{$companyId}", $data, $options);

        return $this->hydrate(Company::class, $this->unwrap($this->decodeBody($response->body), 'companies'));
    }

    /**
     * @return array{deleted: bool, id: string}
     */
    public function remove(string $companyId, ?RequestOptions $options = null): array
    {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->httpDelete("/companies/{$companyId}", $options);

        return ['deleted' => $response->isSuccess(), 'id' => $companyId];
    }

    /**
     * Find a company by federal tax number (CNPJ). Returns `null` if not found.
     *
     * Convenience helper: list-all + client-side filter. For large accounts,
     * prefer using API-side search if/when available.
     */
    public function findByTaxNumber(string|int $taxNumber, ?RequestOptions $options = null): ?Company
    {
        $needle = (string) $taxNumber;
        foreach ($this->listAll($options) as $company) {
            if ((string) $company->federalTaxNumber === $needle) {
                return $company;
            }
        }
        return null;
    }

    /**
     * Find companies whose name contains the given substring (case-insensitive).
     *
     * @return list<Company>
     */
    public function findByName(string $name, ?RequestOptions $options = null): array
    {
        $needle = mb_strtolower($name);
        $matches = [];
        foreach ($this->listAll($options) as $company) {
            if ($company->name !== null && str_contains(mb_strtolower($company->name), $needle)) {
                $matches[] = $company;
            }
        }
        return $matches;
    }

    /**
     * Get the certificate status snapshot for a company.
     *
     * Computes `daysUntilExpiration` and `isExpiringSoon` client-side from
     * the `expiresOn` field when present.
     */
    public function getCertificateStatus(
        string $companyId,
        int $expiringSoonThreshold = 30,
        ?RequestOptions $options = null,
    ): CertificateStatus {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->httpGet("/companies/{$companyId}/certificate", options: $options);
        $payload = $this->decodeBody($response->body);

        $hasCertificate = (bool) ($payload['hasCertificate'] ?? false);
        $expiresOn = isset($payload['expiresOn']) && is_string($payload['expiresOn']) ? $payload['expiresOn'] : null;
        $isValid = isset($payload['isValid']) ? (bool) $payload['isValid'] : null;

        $daysUntilExpiration = null;
        $isExpiringSoon = null;
        if ($hasCertificate && $expiresOn !== null) {
            try {
                $expiresAt = new DateTimeImmutable($expiresOn);
                $now = new DateTimeImmutable('now');
                $diff = (int) $now->diff($expiresAt)->format('%r%a');
                $daysUntilExpiration = $diff;
                $isExpiringSoon = $diff <= $expiringSoonThreshold;
            } catch (Exception) {
                // Unparseable date — leave both null.
            }
        }

        return new CertificateStatus(
            hasCertificate: $hasCertificate,
            expiresOn: $expiresOn,
            isValid: $isValid,
            daysUntilExpiration: $daysUntilExpiration,
            isExpiringSoon: $isExpiringSoon,
            details: $payload,
        );
    }

    /**
     * @return array{expiring: bool, daysLeft: int|null}
     */
    public function checkCertificateExpiration(
        string $companyId,
        int $thresholdDays = 30,
        ?RequestOptions $options = null,
    ): array {
        $status = $this->getCertificateStatus($companyId, $thresholdDays, $options);
        return [
            'expiring' => $status->isExpiringSoon ?? false,
            'daysLeft' => $status->daysUntilExpiration,
        ];
    }

    /**
     * @return list<Company>
     */
    public function getCompaniesWithCertificates(?RequestOptions $options = null): array
    {
        $matches = [];
        foreach ($this->listAll($options) as $company) {
            if ($company->id === null) {
                continue;
            }
            try {
                $status = $this->getCertificateStatus($company->id, options: $options);
                if ($status->hasCertificate) {
                    $matches[] = $company;
                }
            } catch (Throwable) {
                // Skip companies for which we cannot fetch certificate status.
            }
        }
        return $matches;
    }

    /**
     * @return list<Company>
     */
    public function getCompaniesWithExpiringCertificates(
        int $thresholdDays = 30,
        ?RequestOptions $options = null,
    ): array {
        $matches = [];
        foreach ($this->listAll($options) as $company) {
            if ($company->id === null) {
                continue;
            }
            try {
                $status = $this->getCertificateStatus($company->id, $thresholdDays, $options);
                if ($status->isExpiringSoon === true) {
                    $matches[] = $company;
                }
            } catch (Throwable) {
                // Skip on error.
            }
        }
        return $matches;
    }
}
