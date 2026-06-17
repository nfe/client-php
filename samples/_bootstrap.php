<?php

declare(strict_types=1);

/**
 * Bootstrap compartilhado pelos exemplos.
 *
 * Lê samples/.env (gitignored), valida as credenciais mínimas e instancia
 * um Nfe\Client pronto. Use:
 *
 *     $nfe = require __DIR__ . '/_bootstrap.php';
 *     $companyId = getenv('NFE_COMPANY_ID');
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $env = parse_ini_file($envFile, false, INI_SCANNER_RAW);
    if ($env === false) {
        throw new RuntimeException('Falha ao ler samples/.env');
    }
    foreach ($env as $k => $v) {
        if (getenv($k) === false) {
            putenv("{$k}={$v}");
        }
    }
}

$apiKey = getenv('NFE_API_KEY') ?: throw new RuntimeException(
    'NFE_API_KEY ausente. Copie samples/.env.example para samples/.env e preencha.',
);

return new Nfe\Client(
    apiKey:     $apiKey,
    dataApiKey: getenv('NFE_DATA_API_KEY') ?: null,
);
