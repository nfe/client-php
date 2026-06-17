<?php

declare(strict_types=1);

namespace Nfe\Tests\Integration;

/**
 * Smoke read-only contra address.api.nfe.io.
 *
 * CEP 01310-100 = Av. Paulista, São Paulo (sempre existe; bom canário).
 */
final class AddressesIntegrationTest extends IntegrationTestCase
{
    public function test_lookup_by_postal_code_returns_at_least_one_address(): void
    {
        $resultado = $this->nfe->addresses->lookupByPostalCode('01310-100');

        $this->assertNotEmpty($resultado->addresses, 'Esperado pelo menos um endereço para 01310-100.');
        $first = $resultado->addresses[0];
        $this->assertSame('SP', $first['state'] ?? null);
    }

    public function test_lookup_normalises_cep_with_punctuation(): void
    {
        $comHifen = $this->nfe->addresses->lookupByPostalCode('01310-100');
        $semHifen = $this->nfe->addresses->lookupByPostalCode('01310100');

        $this->assertSameSize($comHifen->addresses, $semHifen->addresses);
    }
}
