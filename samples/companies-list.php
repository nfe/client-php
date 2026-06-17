<?php

declare(strict_types=1);

/**
 * Lista as empresas cadastradas na conta e mostra status de certificado A1.
 *
 * Uso:
 *     php samples/companies-list.php
 */

$nfe = require __DIR__ . '/_bootstrap.php';

$lista = $nfe->companies->listAll();

if (count($lista) === 0) {
    echo "Nenhuma empresa cadastrada.\n";
    exit(0);
}

printf("%-36s  %-30s  %-12s  %s\n", 'ID', 'Nome', 'Ambiente', 'Certificado');
echo str_repeat('-', 100) . "\n";

foreach ($lista as $empresa) {
    $nome = substr($empresa->name ?? '?', 0, 30);
    printf(
        "%-36s  %-30s  %-12s  %s\n",
        $empresa->id ?? '?',
        $nome,
        $empresa->environment ?? '?',
        '...',
    );
}

echo "\nTotal: " . count($lista) . " empresa(s)\n";
