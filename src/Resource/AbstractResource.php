<?php

declare(strict_types=1);

namespace Nfe\Resource;

use JsonException;
use Nfe\Client;
use Nfe\Exception\ApiConnectionException;
use Nfe\Exception\ErrorFactory;
use Nfe\Exception\InvalidRequestException;
use Nfe\Http\Request;
use Nfe\Http\RequestOptions;
use Nfe\Http\Response;
use Nfe\Response\InvoiceResponse;
use ReflectionClass;

/**
 * Base class for every resource.
 *
 * Subclasses declare which API host and version they target (via
 * {@see self::apiFamily()} and {@see self::apiVersion()}) and use the
 * protected `get`/`post`/`put`/`delete` helpers to issue requests. The
 * `Client` injects authentication and User-Agent headers around the call.
 */
abstract class AbstractResource
{
    public function __construct(protected readonly Client $client) {}

    /**
     * Identifier of the API family this resource belongs to. Used by
     * {@see \Nfe\Config::baseUrlForApi()} to resolve the target host.
     */
    abstract protected function apiFamily(): string;

    /**
     * API version segment (e.g., "v1", "v2", "v3").
     */
    abstract protected function apiVersion(): string;

    /**
     * Internal HTTP helpers. Named with `http` prefix so subclasses can freely
     * declare public CRUD methods named `get`, `post`, `put`, `delete` with
     * their own signatures without LSP conflicts.
     *
     * @param array<string, scalar|array<int, scalar>> $query
     */
    protected function httpGet(string $path, array $query = [], ?RequestOptions $options = null): Response
    {
        return $this->send('GET', $path, query: $query, options: $options);
    }

    protected function httpPost(string $path, mixed $body = null, ?RequestOptions $options = null): Response
    {
        return $this->send('POST', $path, body: $body, options: $options);
    }

    protected function httpPut(string $path, mixed $body = null, ?RequestOptions $options = null): Response
    {
        return $this->send('PUT', $path, body: $body, options: $options);
    }

    protected function httpDelete(string $path, ?RequestOptions $options = null): Response
    {
        return $this->send('DELETE', $path, options: $options);
    }

    /**
     * Compose the full path with the API version prefix.
     *
     * When `apiVersion()` returns an empty string (because the version is part of
     * the base URL — e.g., `AddressesResource` with `address.api.nfe.io/v2`), the
     * version segment is omitted to avoid producing `//path`.
     */
    protected function fullPath(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        $version = $this->apiVersion();
        return $version === '' ? $path : '/' . $version . $path;
    }

    /**
     * Baixa bytes brutos (PDF/XML) de um endpoint de download.
     *
     * A API NFE.io frequentemente responde com HTTP 302/303 + header `Location`
     * apontando para o CDN/S3 onde o documento está armazenado. Este helper
     * segue o redirect explicitamente em uma segunda requisição **sem** o
     * header `Authorization`, evitando vazar a API key para o host de destino.
     *
     * @param array<string, scalar|array<int, scalar>> $query
     */
    protected function download(string $path, array $query = [], ?RequestOptions $options = null): string
    {
        $response = $this->httpGet($path, $query, $options);

        if ($response->statusCode === 302 || $response->statusCode === 303 || $response->statusCode === 307) {
            $location = $response->header('location');
            if (is_string($location) && $location !== '') {
                return $this->followDownloadRedirect($location);
            }
        }

        if (!$response->isSuccess()) {
            throw ErrorFactory::fromResponse($response);
        }
        return $response->body;
    }

    /**
     * Segue um redirect de download via cURL plain, sem credenciais.
     *
     * O CDN da NFE.io não exige `Authorization` (a URL pré-assinada já carrega
     * o token de acesso na query string). Reusar o transport completo do SDK
     * aqui incluiria `Authorization` automaticamente, expondo a chave para um
     * host fora do controle da NFE.io. Por isso usamos cURL direto e plain.
     */
    private function followDownloadRedirect(string $url): string
    {
        if ($url === '') {
            throw new ApiConnectionException('Redirect com Location vazio.');
        }
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 3);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Accept: */*']);

        $body = curl_exec($curl);
        $err = curl_error($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($body === false || $err !== '') {
            throw new ApiConnectionException("Falha ao baixar arquivo do CDN: {$err}");
        }
        if ($status < 200 || $status >= 300) {
            throw new ApiConnectionException("CDN retornou HTTP {$status} ao baixar o arquivo.");
        }
        return (string) $body;
    }

    /**
     * Unwrap a single-key envelope from a response payload.
     *
     * Many NFE.io endpoints wrap their content under a plural-name key
     * (e.g., `{companies: {...}}`, `{legalPeople: {...}}`). This helper
     * unwraps when the key is present and points to an associative payload;
     * otherwise returns the payload as-is.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    protected function unwrap(array $payload, string $key): array
    {
        if (isset($payload[$key]) && is_array($payload[$key])) {
            /** @var array<string, mixed> $inner */
            $inner = $payload[$key];
            return $inner;
        }
        return $payload;
    }

    /**
     * Hydrate a list response into a {@see ListResponse}, detecting page-style vs cursor-style
     * pagination from the payload shape.
     *
     * @template T of object
     * @param class-string<T> $itemClass DTO class for items
     * @param array<string, mixed> $payload Raw response payload
     * @param string $wrapperKey Key carrying the items array (e.g. 'companies', 'serviceInvoices')
     * @return \Nfe\Util\ListResponse<T>
     */
    protected function hydrateList(string $itemClass, array $payload, string $wrapperKey): \Nfe\Util\ListResponse
    {
        $items = [];
        if (isset($payload[$wrapperKey]) && is_array($payload[$wrapperKey])) {
            foreach ($payload[$wrapperKey] as $itemData) {
                if (is_array($itemData)) {
                    /** @var array<string, mixed> $itemData */
                    $items[] = $this->hydrate($itemClass, $itemData);
                }
            }
        }

        $page = new \Nfe\Util\ListPage(
            pageIndex: isset($payload['pageIndex']) && is_int($payload['pageIndex']) ? $payload['pageIndex'] : null,
            pageCount: isset($payload['pageCount']) && is_int($payload['pageCount']) ? $payload['pageCount'] : null,
            startingAfter: isset($payload['startingAfter']) && is_string($payload['startingAfter']) ? $payload['startingAfter'] : null,
            endingBefore: isset($payload['endingBefore']) && is_string($payload['endingBefore']) ? $payload['endingBefore'] : null,
            total: isset($payload['total']) && is_int($payload['total']) ? $payload['total'] : null,
        );

        return new \Nfe\Util\ListResponse(data: $items, page: $page);
    }

    /**
     * Convert a 201/202 response from an invoice creation endpoint into the
     * appropriate discriminated `InvoiceResponse`. Concrete resources call
     * this from their `create()` methods.
     *
     * @template TIssued of object
     * @template TPending of InvoiceResponse
     *
     * @param class-string<TIssued>  $issuedDtoClass    DTO class used to materialise a 201 body.
     * @param callable(TIssued):InvoiceResponse $issuedFactory Wraps the DTO in an Issued<T>.
     * @param callable(string $invoiceId, string $location):TPending $pendingFactory Builds a Pending from a 202 Location.
     */
    protected function handleAsyncResponse(
        Response $response,
        string $issuedDtoClass,
        callable $issuedFactory,
        callable $pendingFactory,
    ): InvoiceResponse {
        if ($response->statusCode === 202) {
            $location = $response->header('location');
            if ($location === null || $location === '') {
                throw new InvalidRequestException(
                    'Async (HTTP 202) response received without a Location header.',
                    statusCode: 202,
                    responseHeaders: $response->headers,
                );
            }
            return $pendingFactory($this->extractInvoiceIdFromLocation($location), $location);
        }

        if (!$response->isSuccess()) {
            throw ErrorFactory::fromResponse($response);
        }

        $payload = $this->decodeBody($response->body);
        $dto = $this->hydrate($issuedDtoClass, $payload);

        return $issuedFactory($dto);
    }

    /**
     * Pull the last path segment from a Location header (handles both relative
     * and absolute URL forms).
     */
    protected function extractInvoiceIdFromLocation(string $location): string
    {
        $path = $location;
        if (str_contains($location, '://')) {
            $parsed = parse_url($location, PHP_URL_PATH);
            if (is_string($parsed)) {
                $path = $parsed;
            }
        }

        $segments = array_values(array_filter(explode('/', $path), fn(string $s) => $s !== ''));
        return $segments[array_key_last($segments)] ?? '';
    }

    /**
     * Reflection-based hydration of a `final readonly class` from an associative array.
     *
     * Maps array keys to constructor parameters by name. Missing optional params
     * are left at their default. Extra keys in `$data` are ignored.
     *
     * @template T of object
     * @param class-string<T> $class
     * @param array<string, mixed> $data
     * @return T
     */
    protected function hydrate(string $class, array $data): object
    {
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            /** @var T $instance */
            $instance = $reflection->newInstance();
            return $instance;
        }

        $args = [];
        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $data)) {
                $args[] = $data[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                $args[] = null;
            }
        }

        /** @var T $instance */
        $instance = $reflection->newInstanceArgs($args);
        return $instance;
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeBody(string $body): array
    {
        if ($body === '') {
            return [];
        }
        try {
            $decoded = json_decode($body, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidRequestException(
                "Failed to decode JSON response body: {$e->getMessage()}",
                responseBody: $body,
            );
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, scalar|array<int, scalar>> $query
     */
    private function send(
        string $method,
        string $path,
        array $query = [],
        mixed $body = null,
        ?RequestOptions $options = null,
    ): Response {
        $family = $this->apiFamily();
        // `??` suppresses property access on a null object — `->` is correct here.
        $baseUrl = $options->baseUrl
            ?? $this->client->config->baseUrlForApi($family);

        // Resolve which API key applies to this family. Caller's per-call
        // override (RequestOptions::$apiKey) takes precedence; otherwise pick
        // dataApiKey for data-services families and apiKey for everything else.
        $apiKey = $options->apiKey ?? $this->client->config->apiKeyForApi($family);

        $effectiveOptions = new RequestOptions(
            apiKey: $apiKey,
            baseUrl: $baseUrl,
            timeout: $options->timeout ?? null,
        );

        $encodedBody = $body !== null ? json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) : null;

        $request = new Request(
            method: $method,
            baseUrl: $baseUrl,
            path: $this->fullPath($path),
            headers: [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
            query: $query,
            body: $encodedBody === false ? null : $encodedBody,
            timeout: $effectiveOptions->timeout ?? 0,
        );

        $response = $this->client->send($request, $effectiveOptions);

        // Auto-map 4xx/5xx responses to typed exceptions. 2xx (incluindo 202)
        // e 3xx fluem para o caller — 202 é discriminado por handleAsyncResponse()
        // e 3xx é seguido manualmente por download() (sem vazar Authorization
        // para o CDN). Resources que precisam inspecionar respostas brutas de
        // falha podem capturar a exceção e ler $statusCode / $responseBody.
        if ($response->statusCode >= 400) {
            throw ErrorFactory::fromResponse($response);
        }

        return $response;
    }
}
