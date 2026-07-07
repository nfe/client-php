<?php

declare(strict_types=1);

use Nfe\Exception\ApiConnectionException;
use Nfe\Http\CurlTransport;
use Nfe\Http\FailurePhase;
use Nfe\Http\Request;

it('classifies a DNS resolution failure as ConnectionNotEstablished', function (): void {
    // RFC 6761 guarantees the .invalid TLD never resolves — a deterministic,
    // offline way to force CURLE_COULDNT_RESOLVE_HOST (errno 6).
    $transport = new CurlTransport(defaultTimeout: 2, connectTimeout: 2);
    $request = new Request('GET', 'https://nfe-sdk-nonexistent.invalid', '/v1/x');

    try {
        $transport->send($request);
        $this->fail('Expected ApiConnectionException on DNS failure.');
    } catch (ApiConnectionException $e) {
        expect($e->failurePhase)->toBe(FailurePhase::ConnectionNotEstablished);
        expect($e->curlErrno)->toBe(6); // CURLE_COULDNT_RESOLVE_HOST
    }
});

it('marks a failed cURL init as ConnectionNotEstablished', function (): void {
    // Empty method/URL trips the pre-send guard, which never reaches the wire.
    $transport = new CurlTransport();
    $request = new Request('', 'https://api.nfe.io', '');

    try {
        $transport->send($request);
        $this->fail('Expected ApiConnectionException on empty method/URL.');
    } catch (ApiConnectionException $e) {
        expect($e->failurePhase)->toBe(FailurePhase::ConnectionNotEstablished);
    }
});
