<?php

declare(strict_types=1);

namespace Nfe\Http;

use Nfe\Exception\ApiConnectionException;

/**
 * Default zero-dependency HTTP transport.
 *
 * Uses ext-curl directly. No following of redirects or 202 Location headers
 * (the caller decides). Network-level failures raise {@see ApiConnectionException};
 * HTTP failures are returned as-is for upper layers to interpret.
 */
final class CurlTransport implements Transport
{
    /**
     * cURL error codes that prove the request never reached the server
     * (DNS/proxy resolution, TCP connect, TLS handshake). Any other errno —
     * notably CURLE_OPERATION_TIMEDOUT (28), which does not distinguish a
     * connect-timeout from a read-timeout — is treated as ambiguous.
     *
     * @var list<int>
     */
    private const CONNECTION_NOT_ESTABLISHED_ERRNOS = [
        5,  // CURLE_COULDNT_RESOLVE_PROXY
        6,  // CURLE_COULDNT_RESOLVE_HOST
        7,  // CURLE_COULDNT_CONNECT
        35, // CURLE_SSL_CONNECT_ERROR
    ];

    /**
     * @param int $defaultTimeout Used when {@see Request::$timeout} is 0 (default).
     */
    public function __construct(
        private readonly int $defaultTimeout = 60,
        private readonly int $connectTimeout = 30,
    ) {}

    public function send(Request $request): Response
    {
        $curl = curl_init();
        if ($curl === false) {
            throw new ApiConnectionException(
                'Failed to initialise cURL handle.',
                failurePhase: FailurePhase::ConnectionNotEstablished,
            );
        }

        $headers = $this->serializeHeaders($request->headers);

        // cURL constant types in PHPStan stubs are stricter than runtime requires;
        // we set options one-by-one to sidestep the shape mismatch on curl_setopt_array.
        $url = $request->url();
        $method = strtoupper($request->method);
        if ($url === '' || $method === '') {
            throw new ApiConnectionException(
                'Cannot send request with empty URL or method.',
                failurePhase: FailurePhase::ConnectionNotEstablished,
            );
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt(
            $curl,
            CURLOPT_TIMEOUT,
            $request->timeout > 0 ? $request->timeout : $this->defaultTimeout,
        );
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

        if ($request->body !== null && $request->body !== '') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $request->body);
        }

        $raw = curl_exec($curl);

        // CURLOPT_RETURNTRANSFER=true makes curl_exec return string|false at runtime.
        // PHPStan's stub keeps `true` in the union; narrow with !is_string so the rest
        // of the function sees a definite string.
        if (!is_string($raw)) {
            $errno = curl_errno($curl);
            $msg = curl_error($curl);
            curl_close($curl);

            $phase = in_array($errno, self::CONNECTION_NOT_ESTABLISHED_ERRNOS, true)
                ? FailurePhase::ConnectionNotEstablished
                : FailurePhase::RequestMaybeSent;

            throw new ApiConnectionException(
                "Network error talking to NFE.io ({$errno}): {$msg}",
                previous: null,
                failurePhase: $phase,
                curlErrno: $errno,
            );
        }

        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        curl_close($curl);

        $rawHeaders = substr($raw, 0, $headerSize);
        $body       = substr($raw, $headerSize);

        return new Response(
            statusCode: $statusCode,
            headers: $this->parseHeaders($rawHeaders),
            body: $body,
        );
    }

    /**
     * @param array<string, string> $headers
     * @return array<int, string>
     */
    private function serializeHeaders(array $headers): array
    {
        $out = [];
        foreach ($headers as $name => $value) {
            $out[] = "{$name}: {$value}";
        }
        return $out;
    }

    /**
     * Parse the raw header block returned by cURL.
     *
     * Multiple header blocks may be present (e.g. on 100 Continue or redirect
     * chains); we keep only the final one. Header names are normalised to
     * lowercase. Multi-value headers are joined with ", ".
     *
     * @return array<string, string>
     */
    private function parseHeaders(string $raw): array
    {
        $headers = [];
        $blocks  = preg_split("/\r?\n\r?\n/", trim($raw)) ?: [];
        $final   = end($blocks);

        if ($final === false) {
            return $headers;
        }

        $lines = preg_split("/\r?\n/", $final) ?: [];
        foreach ($lines as $line) {
            $pos = strpos($line, ':');
            if ($pos === false) {
                continue;
            }

            $name  = strtolower(trim(substr($line, 0, $pos)));
            $value = trim(substr($line, $pos + 1));

            if (isset($headers[$name])) {
                $headers[$name] .= ', ' . $value;
            } else {
                $headers[$name] = $value;
            }
        }

        return $headers;
    }
}
