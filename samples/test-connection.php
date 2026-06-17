<?php

declare(strict_types=1);

/**
 * Verifica conectividade básica + credenciais sem fazer escrita.
 *
 * Uso:
 *     php samples/test-connection.php
 *
 * Roda 3 chamadas read-only:
 *  - companies.retrieve(NFE_COMPANY_ID) — valida apiKey + company ID
 *  - addresses.lookupByPostalCode('01310-100') — valida dataApiKey (ou fallback)
 *  - companies.getCertificateStatus(NFE_COMPANY_ID) — diagnóstico de cert A1
 */

use Nfe\Exception\ApiErrorException;

$nfe = require __DIR__ . '/_bootstrap.php';

$companyId = getenv('NFE_COMPANY_ID') ?: throw new RuntimeException('NFE_COMPANY_ID ausente em samples/.env');

function check(string $label, callable $fn): void
{
    echo "[ ] {$label}... ";
    try {
        $resultado = $fn();
        echo "\r[✓] {$label}";
        if ($resultado !== null) {
            echo " — {$resultado}";
        }
        echo "\n";
    } catch (ApiErrorException $e) {
        echo "\r[✗] {$label} — HTTP {$e->statusCode}: {$e->getMessage()}\n";
    } catch (Throwable $e) {
        echo "\r[✗] {$label} — " . $e::class . ": {$e->getMessage()}\n";
    }
}

check('companies.retrieve (apiKey + company)', function () use ($nfe, $companyId) {
    $empresa = $nfe->companies->retrieve($companyId);
    return "{$empresa->name} ({$empresa->environment})";
});

check('addresses.lookupByPostalCode (data services)', function () use ($nfe) {
    $resultado = $nfe->addresses->lookupByPostalCode('01310-100');
    return count($resultado->addresses) . ' endereço(s)';
});

check('companies.getCertificateStatus', function () use ($nfe, $companyId) {
    $status = $nfe->companies->getCertificateStatus($companyId);
    if (!$status->hasCertificate) {
        return 'sem certificado';
    }
    return "expira em {$status->expiresOn} ({$status->daysUntilExpiration} dias)";
});
