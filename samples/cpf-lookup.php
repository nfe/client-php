<?php

declare(strict_types=1);

/**
 * Consulta situação cadastral de CPF.
 *
 * Uso:
 *     php samples/cpf-lookup.php <CPF> <DATA_NASCIMENTO>
 *
 * Exemplo:
 *     php samples/cpf-lookup.php 12345678901 1990-01-15
 */

use Nfe\Exception\AuthorizationException;
use Nfe\Exception\NotFoundException;

$nfe = require __DIR__ . '/_bootstrap.php';

if (($argv[1] ?? '') === '' || ($argv[2] ?? '') === '') {
    fwrite(STDERR, "Uso: php samples/cpf-lookup.php <CPF> <YYYY-MM-DD>\n");
    exit(2);
}

$cpf = $argv[1];
$nascimento = $argv[2];

echo "Consultando CPF {$cpf} (nascido em {$nascimento})...\n";

try {
    $result = $nfe->naturalPersonLookup->getStatus($cpf, $nascimento);
    echo "Nome:     {$result->name}\n";
    echo "Situação: {$result->status}\n";
} catch (NotFoundException) {
    fwrite(STDERR, "CPF não encontrado ou data de nascimento incorreta.\n");
    exit(1);
} catch (AuthorizationException) {
    fwrite(STDERR, "403 — chave sem o plano de serviços de dados.\n");
    exit(1);
}
