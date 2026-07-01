<?php

declare(strict_types=1);

/**
 * Consulta dados cadastrais de empresa por CNPJ.
 *
 * Uso:
 *     php samples/cnpj-lookup.php [CNPJ]
 *
 * Exemplo:
 *     php samples/cnpj-lookup.php 33.000.167/0001-01
 *
 * Requer plano de serviços de dados na chave usada. Configure
 * NFE_DATA_API_KEY em samples/.env quando tiver uma chave dedicada.
 */

use Nfe\Exception\AuthorizationException;

$nfe = require __DIR__ . '/_bootstrap.php';

$cnpj = $argv[1] ?? '33000167000101'; // Petrobras (público)

echo "Consultando CNPJ {$cnpj}...\n";

try {
    $result = $nfe->legalEntityLookup->getBasicInfo($cnpj);
    $pj = $result->legalEntity;

    echo "Razão Social:   " . ($pj['name'] ?? '?') . "\n";
    echo "Nome Fantasia:  " . ($pj['tradeName'] ?? '?') . "\n";
    echo "Status:         " . ($pj['status'] ?? '?') . "\n";
    echo "Porte:          " . ($pj['size'] ?? '?') . "\n";
    echo "Município:      " . ($pj['address']['city']['name'] ?? '?') . "/" . ($pj['address']['state'] ?? '?') . "\n";
} catch (AuthorizationException $e) {
    fwrite(STDERR, "403 — a chave em uso provavelmente não tem o plano de serviços de dados.\n");
    fwrite(STDERR, "Defina NFE_DATA_API_KEY em samples/.env com uma chave que tenha o plano.\n");
    exit(1);
}
