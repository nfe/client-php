<?php

declare(strict_types=1);

/**
 * Lista as NFS-e mais recentes da empresa.
 *
 * Uso:
 *     php samples/service-invoice-list.php [pageCount]
 *
 * Exemplo:
 *     php samples/service-invoice-list.php 20
 */

$nfe = require __DIR__ . '/_bootstrap.php';

$companyId = getenv('NFE_COMPANY_ID') ?: throw new RuntimeException('NFE_COMPANY_ID ausente em samples/.env');
$pageCount = (int) ($argv[1] ?? 10);

echo "Listando últimas {$pageCount} NFS-e da empresa {$companyId}...\n";

// pageIndex é 1-based na API da NFE.io. Use 1 para a primeira página.
$lista = $nfe->serviceInvoices->list($companyId, [
    'pageCount' => $pageCount,
    'pageIndex' => 1,
]);

if (count($lista->data) === 0) {
    echo "Nenhuma NFS-e encontrada.\n";
    exit(0);
}

printf("%-36s  %-15s  %-12s  %s\n", 'ID', 'Status', 'Número', 'Emitida em');
echo str_repeat('-', 90) . "\n";

foreach ($lista->data as $nota) {
    printf(
        "%-36s  %-15s  %-12s  %s\n",
        $nota->id ?? '?',
        $nota->flowStatus ?? '?',
        $nota->number ?? '-',
        $nota->issuedOn ?? '-',
    );
}

echo "\nTotal exibido: " . count($lista->data) . "\n";
