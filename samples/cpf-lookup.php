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

$cpf = $argv[1] ?? getenv('CPF_SAMPLER');
$nascimento = $argv[2] ?? getenv('BIRTHDAY_SAMPLER');

if (!$cpf || !$nascimento) {
    fwrite(STDERR, "Uso: php samples/cpf-lookup.php <CPF> <YYYY-MM-DD>\n");
    fwrite(STDERR, "Ou defina CPF_SAMPLER e BIRTHDAY_SAMPLER em samples/.env\n");
    exit(2);
}

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
