<?php

declare(strict_types=1);

namespace Nfe\Resource;

use Nfe\Exception\InvalidRequestException;
use Nfe\Http\RequestOptions;

/**
 * Tax calculation engine (RTC — Regime Tributário Calculado).
 *
 * Hosted at `https://api.nfse.io` with no API-version prefix (paths
 * already include `/tax-rules/...`).
 */
final class TaxCalculationResource extends AbstractResource
{
    protected function apiFamily(): string
    {
        return 'tax-calculation';
    }

    protected function apiVersion(): string
    {
        return '';
    }

    /**
     * @param array<string, mixed> $request Request shape per `Nfe\Generated\CalculoImpostosV1\CalculateRequest`
     *        (operationType, issuer, recipient, items, ...).
     * @return array<string, mixed> Calculated tax breakdown per item.
     */
    public function calculate(string $tenantId, array $request, ?RequestOptions $options = null): array
    {
        if (trim($tenantId) === '') {
            throw new InvalidRequestException('tenantId é obrigatório.');
        }
        if (!isset($request['operationType']) || !is_string($request['operationType']) || $request['operationType'] === '') {
            throw new InvalidRequestException('Campo `operationType` é obrigatório na requisição de cálculo.');
        }
        if (!isset($request['items']) || !is_array($request['items']) || $request['items'] === []) {
            throw new InvalidRequestException('Campo `items` deve ser um array não vazio.');
        }

        $encoded = rawurlencode(trim($tenantId));
        $response = $this->httpPost("/tax-rules/{$encoded}/engine/calculate", $request, $options);

        return $this->decodeBody($response->body);
    }
}
