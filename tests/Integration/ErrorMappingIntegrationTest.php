<?php

declare(strict_types=1);

namespace Nfe\Tests\Integration;

use Nfe\Client;
use Nfe\Exception\AuthenticationException;
use Nfe\Exception\NotFoundException;

/**
 * Valida que chamadas reais com erros conhecidos produzem as exceções tipadas
 * corretas. Pega regressões silenciosas no ErrorFactory / AbstractResource::send.
 */
final class ErrorMappingIntegrationTest extends IntegrationTestCase
{
    public function test_invalid_api_key_raises_authentication_exception(): void
    {
        $badClient = new Client(apiKey: 'invalid-api-key-12345');

        $this->expectException(AuthenticationException::class);
        $badClient->companies->retrieve($this->companyId);
    }

    public function test_nonexistent_company_raises_not_found(): void
    {
        $this->expectException(NotFoundException::class);
        $this->nfe->companies->retrieve('00000000000000000000000000000000');
    }
}
