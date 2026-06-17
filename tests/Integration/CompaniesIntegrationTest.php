<?php

declare(strict_types=1);

namespace Nfe\Tests\Integration;

/**
 * Smoke read-only de empresas e certificado A1.
 *
 * Não cria nem deleta nada — apenas lê a empresa configurada em
 * NFE_SDK_E2E_COMPANY_ID e os metadados do certificado.
 */
final class CompaniesIntegrationTest extends IntegrationTestCase
{
    public function test_retrieve_returns_company_with_id(): void
    {
        $empresa = $this->nfe->companies->retrieve($this->companyId);

        $this->assertSame($this->companyId, $empresa->id);
        $this->assertNotEmpty($empresa->name);
    }

    public function test_get_certificate_status_returns_dto(): void
    {
        $status = $this->nfe->companies->getCertificateStatus($this->companyId);

        $this->assertIsBool($status->hasCertificate);
        if ($status->hasCertificate) {
            $this->assertNotNull($status->expiresOn);
            $this->assertIsBool($status->isValid);
        }
    }
}
