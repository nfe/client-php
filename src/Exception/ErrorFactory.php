<?php

declare(strict_types=1);

namespace Nfe\Exception;

use Nfe\Http\Response;

/**
 * Maps a non-2xx {@see Response} to the appropriate {@see ApiErrorException}
 * subclass. Used by the resource layer right before returning to the caller.
 */
final class ErrorFactory
{
    public static function fromResponse(Response $response): ApiErrorException
    {
        $payload   = self::decode($response->body);
        $message   = self::extractMessage($payload) ?? "API request failed with HTTP {$response->statusCode}";
        $errorCode = self::extractCode($payload);

        $class = match (true) {
            $response->statusCode === 400 => InvalidRequestException::class,
            $response->statusCode === 401 => AuthenticationException::class,
            $response->statusCode === 404 => NotFoundException::class,
            $response->statusCode === 429 => RateLimitException::class,
            $response->statusCode >= 500 && $response->statusCode <= 599 => ServerException::class,
            default => InvalidRequestException::class,
        };

        return new $class(
            message: $message,
            statusCode: $response->statusCode,
            responseBody: $response->body,
            responseHeaders: $response->headers,
            errorCode: $errorCode,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function decode(string $body): ?array
    {
        if ($body === '') {
            return null;
        }

        try {
            $decoded = json_decode($body, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    private static function extractMessage(?array $payload): ?string
    {
        if ($payload === null) {
            return null;
        }

        foreach (['message', 'error', 'detail'] as $key) {
            if (isset($payload[$key]) && is_string($payload[$key])) {
                return $payload[$key];
            }
        }

        if (isset($payload['errors'])) {
            if (is_string($payload['errors'])) {
                return $payload['errors'];
            }
            if (is_array($payload['errors'])) {
                $first = reset($payload['errors']);
                if (is_string($first)) {
                    return $first;
                }
                if (is_array($first) && isset($first['message']) && is_string($first['message'])) {
                    return $first['message'];
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    private static function extractCode(?array $payload): ?string
    {
        if ($payload === null) {
            return null;
        }

        foreach (['code', 'errorCode', 'error_code'] as $key) {
            if (isset($payload[$key]) && (is_string($payload[$key]) || is_int($payload[$key]))) {
                return (string) $payload[$key];
            }
        }

        return null;
    }
}
