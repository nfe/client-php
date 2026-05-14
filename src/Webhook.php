<?php

declare(strict_types=1);

namespace Nfe;

use JsonException;
use Nfe\Exception\InvalidRequestException;
use Nfe\Exception\SignatureVerificationException;

/**
 * Static helper for verifying inbound NFE.io webhook signatures.
 *
 * Uses HMAC-SHA1 by default — the canonical scheme confirmed with the NFE.io
 * API team on 2026-05-13 and validated against the production
 * `nfeio-whmcs-modulo` v3.2.0 implementation.
 *
 * Two API levels:
 *
 *   - High-level: {@see constructEvent()} — validates the signature, parses
 *     the payload (unwrapping the v2 `{action, payload}` envelope when
 *     present), and returns a {@see WebhookEvent}. Raises
 *     {@see SignatureVerificationException} on mismatch.
 *
 *   - Low-level: {@see verifySignature()} — returns a boolean. Useful when
 *     you want to log invalid signatures without throwing.
 *
 * Example (vanilla):
 *
 *     try {
 *         $event = Nfe\Webhook::constructEvent(
 *             payload:   file_get_contents('php://input'),
 *             sigHeader: $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '',
 *             secret:    $_ENV['NFE_WEBHOOK_SECRET'],
 *         );
 *         // process $event->type / $event->data
 *     } catch (Nfe\Exception\SignatureVerificationException) {
 *         http_response_code(403);
 *         exit;
 *     }
 */
final class Webhook
{
    /** Static-only helper; never instantiated. */
    private function __construct() {}

    /**
     * Verify an HMAC signature against the payload and secret.
     *
     * Accepts both prefixed (`sha1=AD0A...`) and bare hex signatures. Uses
     * {@see hash_equals()} for timing-safe comparison. Returns `false` for
     * malformed inputs, algorithm downgrades (header says `sha256=` but
     * `$algo` is `sha1`), or any mismatch.
     */
    public static function verifySignature(
        string $payload,
        string $signature,
        string $secret,
        string $algo = 'sha1',
    ): bool {
        $signature = trim($signature);
        if ($signature === '') {
            return false;
        }

        if (str_contains($signature, '=')) {
            [$headerAlgo, $hex] = explode('=', $signature, 2);
            if (strtolower($headerAlgo) !== strtolower($algo)) {
                // Algorithm downgrade / mismatch — refuse.
                return false;
            }
        } else {
            $hex = $signature;
        }

        if ($hex === '' || !ctype_xdigit($hex)) {
            return false;
        }

        $computed = hash_hmac($algo, $payload, $secret);

        return hash_equals(strtolower($computed), strtolower($hex));
    }

    /**
     * Verify the signature and return a parsed {@see WebhookEvent}.
     *
     * The payload is expected to be the raw HTTP request body (UTF-8 JSON).
     * If it carries the NFE.io v2 envelope `{action: <type>, payload: <data>}`,
     * the envelope is unwrapped: `$event->type` will be `action` and
     * `$event->data` will be the inner `payload` object.
     *
     * For payloads without the envelope, the helper looks for any string-shaped
     * `type`/`event_type`/`action` field; otherwise raises
     * {@see InvalidRequestException}.
     *
     * @throws SignatureVerificationException When the HMAC verification fails.
     * @throws InvalidRequestException        When the JSON is malformed or
     *                                         a recognisable event type cannot be located.
     */
    public static function constructEvent(
        string $payload,
        string $sigHeader,
        string $secret,
        string $algo = 'sha1',
    ): WebhookEvent {
        if (!self::verifySignature($payload, $sigHeader, $secret, $algo)) {
            throw new SignatureVerificationException(
                message: 'Webhook signature verification failed.',
                statusCode: 403,
                responseBody: $payload,
            );
        }

        try {
            $decoded = json_decode($payload, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidRequestException(
                message: 'Webhook payload is not valid JSON: ' . $e->getMessage(),
                responseBody: $payload,
            );
        }

        if (!is_array($decoded)) {
            throw new InvalidRequestException(
                message: 'Webhook payload did not decode to an object/array.',
                responseBody: $payload,
            );
        }

        // v2 envelope: { action: 'invoice.issued', payload: {...} }
        if (
            isset($decoded['action'], $decoded['payload'])
            && is_string($decoded['action'])
            && is_array($decoded['payload'])
        ) {
            return new WebhookEvent(
                type: $decoded['action'],
                data: $decoded['payload'],
                id: self::asNullableString($decoded['id'] ?? $decoded['payload']['id'] ?? null),
                createdAt: self::asNullableString($decoded['createdAt'] ?? $decoded['payload']['createdAt'] ?? null),
            );
        }

        // Fallback: flat payload with a 'type' / 'event_type' / 'action' field
        $type = self::firstString($decoded, ['type', 'event_type', 'action']);
        if ($type === null) {
            throw new InvalidRequestException(
                message: 'Webhook payload has no recognisable event type (looked for action/type/event_type).',
                responseBody: $payload,
            );
        }

        return new WebhookEvent(
            type: $type,
            data: $decoded,
            id: self::asNullableString($decoded['id'] ?? null),
            createdAt: self::asNullableString($decoded['createdAt'] ?? null),
        );
    }

    /**
     * @param array<string, mixed> $arr
     * @param list<string> $keys
     */
    private static function firstString(array $arr, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($arr[$key]) && is_string($arr[$key]) && $arr[$key] !== '') {
                return $arr[$key];
            }
        }
        return null;
    }

    private static function asNullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }
}
