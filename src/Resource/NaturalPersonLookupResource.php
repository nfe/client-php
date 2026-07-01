<?php

declare(strict_types=1);

namespace Nfe\Resource;

use DateTimeImmutable;
use Nfe\Http\RequestOptions;
use Nfe\Resource\Dto\NaturalPersonLookup\NaturalPersonStatus;
use Nfe\Util\DateNormalizer;
use Nfe\Util\IdValidator;

/**
 * CPF cadastral status lookup against the NFE.io natural-person API.
 *
 * Hosted at `https://naturalperson.api.nfe.io` under v1.
 */
final class NaturalPersonLookupResource extends AbstractResource
{
    protected function apiFamily(): string
    {
        return 'natural-person';
    }

    protected function apiVersion(): string
    {
        return 'v1';
    }

    public function getStatus(
        string $cpf,
        string|DateTimeImmutable $birthDate,
        ?RequestOptions $options = null,
    ): NaturalPersonStatus {
        $cpf = IdValidator::cpf($cpf);
        $date = DateNormalizer::toIsoDate($birthDate);
        $response = $this->httpGet("/naturalperson/status/{$cpf}/{$date}", options: $options);
        $payload = $this->decodeBody($response->body);

        return $this->hydrate(NaturalPersonStatus::class, $payload);
    }
}
