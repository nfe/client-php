<?php

declare(strict_types=1);

namespace Nfe\Resource\Dto\Companies;

/**
 * Certificate status snapshot for a company.
 *
 * Shape mirrors the Node SDK's `getCertificateStatus()` return type (canonical
 * fields confirmed in `companies.ts:459-491`). `daysUntilExpiration` and
 * `isExpiringSoon` are computed client-side when the API returns `expiresOn`.
 */
final readonly class CertificateStatus
{
    /**
     * @param array<string, mixed>|null $details Raw API payload kept for forward-compat.
     */
    public function __construct(
        public bool $hasCertificate,
        public ?string $expiresOn = null,
        public ?bool $isValid = null,
        public ?int $daysUntilExpiration = null,
        public ?bool $isExpiringSoon = null,
        public ?array $details = null,
    ) {}
}
