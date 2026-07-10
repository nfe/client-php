<?php

declare(strict_types=1);

/**
 * Emite uma NFS-e e aguarda o processamento assíncrono via polling manual.
 *
 * Uso:
 *     php samples/service-invoice-issue.php
 *
 * IMPORTANTE:
 *  - A empresa em NFE_COMPANY_ID precisa ter certificado A1 válido e
 *    provedor municipal NFS-e configurado.
 *  - O payload abaixo é mínimo; ajuste cityServiceCode/servicesAmount
 *    conforme a sua atividade.
 */

use Nfe\Exception\ApiErrorException;
use Nfe\Response\Pending;
use Nfe\Util\FlowStatus;

$nfe = require __DIR__ . '/_bootstrap.php';

$companyId = getenv('NFE_COMPANY_ID') ?: throw new RuntimeException('NFE_COMPANY_ID ausente em samples/.env');

$payload = [
    'borrower' => [
        'federalTaxNumber' => 12345678901,
        'name'             => 'Tomador de Teste',
        'email'            => 'teste@example.com',
    ],
    'cityServiceCode' => '01234',
    'description'     => 'Serviços de teste do SDK',
    'servicesAmount'  => 1.00,
];

echo "Emitindo NFS-e...\n";

try {
    $result = $nfe->serviceInvoices->create($companyId, $payload);

    if (!$result instanceof Pending) {
        echo "Nota emitida síncrona: ID={$result->resource()->id}\n";
        exit(0);
    }

    $invoiceId = $result->invoiceId();
    echo "Aceita assíncrona (202). invoiceId={$invoiceId}. Aguardando processamento...\n";

    $tentativas = 0;
    $maxTentativas = 30;
    do {
        sleep(2);
        $tentativas++;
        $invoice = $nfe->serviceInvoices->retrieve($companyId, $invoiceId);
        echo "  [{$tentativas}/{$maxTentativas}] status={$invoice->flowStatus}\n";

        if ($tentativas >= $maxTentativas) {
            fwrite(STDERR, "Timeout aguardando estado terminal.\n");
            exit(1);
        }
    } while (!FlowStatus::isTerminal($invoice->flowStatus));

    if ($invoice->flowStatus === 'Issued') {
        echo "✓ Nota emitida: id={$invoice->id}, número={$invoice->number}, código={$invoice->checkCode}\n";
        // Campos não tipados continuam acessíveis via ->raw (ex.: tributos):
        echo "  ISS: base={$invoice->baseTaxAmount} alíquota={$invoice->issRate} valor={$invoice->issTaxAmount}\n";
        exit(0);
    }

    fwrite(STDERR, "Estado terminal de falha: {$invoice->flowStatus}\n");
    exit(1);
} catch (ApiErrorException $e) {
    fwrite(STDERR, "Erro HTTP {$e->statusCode}: {$e->getMessage()}\n");
    if ($e->responseBody !== '') {
        fwrite(STDERR, "Body: {$e->responseBody}\n");
    }
    exit(1);
}
