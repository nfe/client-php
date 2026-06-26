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
    // Parser simples para evitar problemas do parse_ini_file com caracteres
    // especiais nos valores (parênteses, ?, &, etc. — comuns em chaves de API).
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines ?: [] as $line) {
        $line = ltrim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $eq = strpos($line, '=');
        if ($eq === false) {
            continue;
        }
        $key = trim(substr($line, 0, $eq));
        $value = trim(substr($line, $eq + 1));
        // Aceita "value", 'value' ou value sem aspas.
        if (strlen($value) >= 2
            && (($value[0] === '"' && $value[-1] === '"') || ($value[0] === "'" && $value[-1] === "'"))
        ) {
            $value = substr($value, 1, -1);
        }
        if ($key !== '' && getenv($key) === false) {
            putenv("{$key}={$value}");
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
