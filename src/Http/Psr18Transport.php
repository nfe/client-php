<?php

declare(strict_types=1);

namespace Nfe\Http;

use Nfe\Exception\ApiConnectionException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Adapter that lets any PSR-18 HTTP client back the SDK's transport interface.
 *
 * This class is the escape hatch for integrators who already have Guzzle,
 * Symfony HttpClient, or another PSR-18 client wired up and want the SDK to
 * reuse it (sharing connection pools, instrumentation, mocking layers, etc.).
 *
 * The SDK does not declare `psr/http-client` as a runtime dependency. Bringing
 * the PSR-18 packages and a concrete client is the consumer's responsibility:
 *
 *     composer require guzzlehttp/guzzle nyholm/psr7
 *
 *     $psr18 = new GuzzleHttp\Client();
 *     $factory = new Nyholm\Psr7\Factory\Psr17Factory();
 *     $transport = new Nfe\Http\Psr18Transport($psr18, $factory, $factory);
 *     $nfe = new Nfe\Client(apiKey: '...', transport: $transport);
 */
final class Psr18Transport implements Transport
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {}

    public function send(Request $request): Response
    {
        $psrRequest = $this->requestFactory->createRequest($request->method, $request->url());

        foreach ($request->headers as $name => $value) {
            $psrRequest = $psrRequest->withHeader($name, $value);
        }

        if ($request->body !== null && $request->body !== '') {
            $psrRequest = $psrRequest->withBody($this->streamFactory->createStream($request->body));
        }

        try {
            $psrResponse = $this->client->sendRequest($psrRequest);
        } catch (ClientExceptionInterface $e) {
            throw new ApiConnectionException(
                "PSR-18 transport failed: {$e->getMessage()}",
                previous: $e,
            );
        }

        $headers = [];
        foreach ($psrResponse->getHeaders() as $name => $values) {
            $headers[strtolower($name)] = implode(', ', $values);
        }

        return new Response(
            statusCode: $psrResponse->getStatusCode(),
            headers:    $headers,
            body:       (string) $psrResponse->getBody(),
        );
    }
}
