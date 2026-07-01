<?php

declare(strict_types=1);

/**
 * Consulta CEP por código postal.
 *
 * Uso:
 *     php samples/cep-lookup.php [CEP]
 *
 * Exemplo:
 *     php samples/cep-lookup.php 01310-100
 */

$nfe = require __DIR__ . '/_bootstrap.php';

$cep = $argv[1] ?? '01310-100';

echo "Consultando CEP {$cep}...\n";

$resultado = $nfe->addresses->lookupByPostalCode($cep);

if (count($resultado->addresses) === 0) {
    echo "Nenhum endereço encontrado.\n";
    exit(1);
}

foreach ($resultado->addresses as $endereco) {
    printf(
        "%s, %s — %s/%s — %s\n",
        $endereco['street'] ?? '?',
        $endereco['district'] ?? '?',
        $endereco['city']['name'] ?? '?',
        $endereco['state'] ?? '?',
        $endereco['postalCode'] ?? '?',
    );
}
