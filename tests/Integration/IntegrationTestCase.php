<?php

declare(strict_types=1);

namespace Nfe\Tests\Integration;

use Nfe\Client;
use PHPUnit\Framework\TestCase;

/**
 * Base class para integration tests opt-in contra a API real da NFE.io.
 *
 * Os testes ficam pulados (skip) a menos que TODAS estas variáveis
 * estejam definidas e `NFE_SDK_E2E === '1'`:
 *
 *   - NFE_SDK_E2E=1
 *   - NFE_SDK_E2E_API_KEY=<chave>
 *   - NFE_SDK_E2E_COMPANY_ID=<id>
 *
 * Variáveis opcionais (testes específicos checam):
 *   - NFE_SDK_E2E_DATA_API_KEY=<chave de serviços de dados>
 *   - NFE_SDK_E2E_WEBHOOK_SECRET=<segredo HMAC>
 */
abstract class IntegrationTestCase extends TestCase
{
    protected Client $nfe;
    protected string $companyId;

    protected function setUp(): void
    {
        if (getenv('NFE_SDK_E2E') !== '1') {
            $this->markTestSkipped('Integration tests off. Set NFE_SDK_E2E=1 to enable.');
        }

        $apiKey = getenv('NFE_SDK_E2E_API_KEY');
        if (!is_string($apiKey) || $apiKey === '') {
            $this->markTestSkipped('NFE_SDK_E2E_API_KEY ausente.');
        }

        $companyId = getenv('NFE_SDK_E2E_COMPANY_ID');
        if (!is_string($companyId) || $companyId === '') {
            $this->markTestSkipped('NFE_SDK_E2E_COMPANY_ID ausente.');
        }

        $this->nfe = new Client(
            apiKey: $apiKey,
            dataApiKey: getenv('NFE_SDK_E2E_DATA_API_KEY') ?: null,
        );
        $this->companyId = $companyId;
    }

    /**
     * Skip o teste se a variável opcional não estiver definida.
     */
    protected function requireEnv(string $name): string
    {
        $value = getenv($name);
        if (!is_string($value) || $value === '') {
            $this->markTestSkipped("Variável {$name} ausente — pulando teste que depende dela.");
        }
        return $value;
    }
}
