<?php

declare(strict_types=1);

/**
 * Baixa PDF e XML de uma NFS-e específica.
 *
 * Uso:
 *     php samples/service-invoice-download.php <INVOICE_ID> [DIR_DESTINO]
 *
 * Exemplo:
 *     php samples/service-invoice-download.php abc123 /tmp
 */

use Nfe\Exception\NotFoundException;

$nfe = require __DIR__ . '/_bootstrap.php';

$companyId = getenv('NFE_COMPANY_ID') ?: throw new RuntimeException('NFE_COMPANY_ID ausente em samples/.env');
$invoiceId = $argv[1] ?? throw new RuntimeException('Passe o INVOICE_ID como argumento.');
$dir = $argv[2] ?? __DIR__;

if (!is_dir($dir) || !is_writable($dir)) {
    fwrite(STDERR, "Diretório destino inválido ou sem permissão de escrita: {$dir}\n");
    exit(2);
}

echo "Baixando PDF e XML da nota {$invoiceId}...\n";

try {
    $pdf = $nfe->serviceInvoices->downloadPdf($companyId, $invoiceId);
    $pdfPath = "{$dir}/nfse-{$invoiceId}.pdf";
    file_put_contents($pdfPath, $pdf);
    echo "  PDF salvo: {$pdfPath} (" . strlen($pdf) . " bytes)\n";

    $xml = $nfe->serviceInvoices->downloadXml($companyId, $invoiceId);
    $xmlPath = "{$dir}/nfse-{$invoiceId}.xml";
    file_put_contents($xmlPath, $xml);
    echo "  XML salvo: {$xmlPath} (" . strlen($xml) . " bytes)\n";
} catch (NotFoundException) {
    fwrite(STDERR, "Nota {$invoiceId} não encontrada.\n");
    exit(1);
}
