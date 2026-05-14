<?php

declare(strict_types=1);

namespace Nfe\Resource;

use Nfe\Http\RequestOptions;
use Nfe\Resource\Dto\StateTaxes\NfeStateTax;
use Nfe\Util\IdValidator;
use Nfe\Util\ListResponse;

/**
 * State tax registrations (Inscrições Estaduais) per company.
 *
 * Hosted at `https://api.nfse.io` under v2. Pagination is cursor-style
 * (`startingAfter`, `endingBefore`, `limit`).
 *
 * Create and update wrap the body as `{stateTax: <data>}` per the canonical
 * Node SDK behaviour.
 */
final class StateTaxesResource extends AbstractResource
{
    protected function apiFamily(): string
    {
        return 'state-taxes';
    }

    protected function apiVersion(): string
    {
        return 'v2';
    }

    /**
     * @param array{startingAfter?: string, endingBefore?: string, limit?: int}|null $opts
     * @return ListResponse<NfeStateTax>
     */
    public function list(
        string $companyId,
        ?array $opts = null,
        ?RequestOptions $options = null,
    ): ListResponse {
        $companyId = IdValidator::companyId($companyId);
        $query = [];
        if ($opts !== null) {
            if (isset($opts['startingAfter'])) {
                $query['startingAfter'] = $opts['startingAfter'];
            }
            if (isset($opts['endingBefore'])) {
                $query['endingBefore'] = $opts['endingBefore'];
            }
            if (isset($opts['limit'])) {
                $query['limit'] = $opts['limit'];
            }
        }
        $response = $this->httpGet("/companies/{$companyId}/statetaxes", $query, $options);
        $payload = $this->decodeBody($response->body);

        return $this->hydrateList(NfeStateTax::class, $payload, 'stateTaxes');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(string $companyId, array $data, ?RequestOptions $options = null): NfeStateTax
    {
        $companyId = IdValidator::companyId($companyId);
        $response = $this->httpPost("/companies/{$companyId}/statetaxes", ['stateTax' => $data], $options);

        return $this->hydrate(NfeStateTax::class, $this->decodeBody($response->body));
    }

    public function retrieve(string $companyId, string $stateTaxId, ?RequestOptions $options = null): NfeStateTax
    {
        $companyId = IdValidator::companyId($companyId);
        $stateTaxId = IdValidator::stateTaxId($stateTaxId);
        $response = $this->httpGet("/companies/{$companyId}/statetaxes/{$stateTaxId}", options: $options);

        return $this->hydrate(NfeStateTax::class, $this->decodeBody($response->body));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(
        string $companyId,
        string $stateTaxId,
        array $data,
        ?RequestOptions $options = null,
    ): NfeStateTax {
        $companyId = IdValidator::companyId($companyId);
        $stateTaxId = IdValidator::stateTaxId($stateTaxId);
        $response = $this->httpPut(
            "/companies/{$companyId}/statetaxes/{$stateTaxId}",
            ['stateTax' => $data],
            $options,
        );

        return $this->hydrate(NfeStateTax::class, $this->decodeBody($response->body));
    }

    public function delete(string $companyId, string $stateTaxId, ?RequestOptions $options = null): void
    {
        $companyId = IdValidator::companyId($companyId);
        $stateTaxId = IdValidator::stateTaxId($stateTaxId);
        $this->httpDelete("/companies/{$companyId}/statetaxes/{$stateTaxId}", $options);
    }
}
