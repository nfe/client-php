<?php

declare(strict_types=1);

namespace Nfe\Resource;

use Nfe\Http\RequestOptions;
use Nfe\Resource\Dto\LegalEntityLookup\LegalEntityResponse;
use Nfe\Util\IdValidator;

/**
 * CNPJ lookup against the NFE.io legal-entity API.
 *
 * Hosted at `https://legalentity.api.nfe.io` under v2.
 */
final class LegalEntityLookupResource extends AbstractResource
{
    protected function apiFamily(): string
    {
        return 'legal-entity';
    }

    protected function apiVersion(): string
    {
        return 'v2';
    }

    /**
     * @param array{updateAddress?: bool, updateCityCode?: bool}|null $opts
     */
    public function getBasicInfo(
        string $cnpj,
        ?array $opts = null,
        ?RequestOptions $options = null,
    ): LegalEntityResponse {
        $cnpj = IdValidator::cnpj($cnpj);
        $query = [];
        if ($opts !== null) {
            if (isset($opts['updateAddress'])) {
                $query['updateAddress'] = $opts['updateAddress'] ? 'true' : 'false';
            }
            if (isset($opts['updateCityCode'])) {
                $query['updateCityCode'] = $opts['updateCityCode'] ? 'true' : 'false';
            }
        }
        $response = $this->httpGet("/legalentities/basicInfo/{$cnpj}", $query, $options);

        return $this->buildResponse($response->body);
    }

    public function getStateTaxInfo(string $state, string $cnpj, ?RequestOptions $options = null): LegalEntityResponse
    {
        $state = IdValidator::state($state);
        $cnpj = IdValidator::cnpj($cnpj);
        $response = $this->httpGet("/legalentities/stateTaxInfo/{$state}/{$cnpj}", options: $options);

        return $this->buildResponse($response->body);
    }

    public function getStateTaxForInvoice(
        string $state,
        string $cnpj,
        ?RequestOptions $options = null,
    ): LegalEntityResponse {
        $state = IdValidator::state($state);
        $cnpj = IdValidator::cnpj($cnpj);
        $response = $this->httpGet("/legalentities/stateTaxForInvoice/{$state}/{$cnpj}", options: $options);

        return $this->buildResponse($response->body);
    }

    public function getSuggestedStateTaxForInvoice(
        string $state,
        string $cnpj,
        ?RequestOptions $options = null,
    ): LegalEntityResponse {
        $state = IdValidator::state($state);
        $cnpj = IdValidator::cnpj($cnpj);
        $response = $this->httpGet(
            "/legalentities/stateTaxSuggestedForInvoice/{$state}/{$cnpj}",
            options: $options,
        );

        return $this->buildResponse($response->body);
    }

    private function buildResponse(string $body): LegalEntityResponse
    {
        $payload = $this->decodeBody($body);
        $entity = isset($payload['legalEntity']) && is_array($payload['legalEntity'])
            ? $payload['legalEntity']
            : null;

        /** @var array<string, mixed>|null $entity */
        return new LegalEntityResponse(legalEntity: $entity, raw: $payload);
    }
}
