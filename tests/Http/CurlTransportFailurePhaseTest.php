<?php

declare(strict_types=1);

use Nfe\Exception\ApiConnectionException;
use Nfe\Http\CurlTransport;
use Nfe\Http\FailurePhase;
use Nfe\Http\Request;

it('classifies a refused connection as ConnectionNotEstablished', function (): void {
    // Forces CURLE_COULDNT_CONNECT (errno 7) without touching DNS: grab a free
    // ephemeral port on the loopback, release it and connect to the IP literal.
    // Nothing listens there, so the kernel answers the SYN with RST at once.
    // A `.invalid` hostname is not enough: networks with wildcard DNS resolve it.
    $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
    if ($server === false) {
        $this->fail("Cannot bind a loopback socket: {$errstr}");
    }
    $address = (string) stream_socket_get_name($server, false);
    fclose($server);

    $transport = new CurlTransport(defaultTimeout: 2, connectTimeout: 2);
    $request = new Request('GET', "http://{$address}", '/v1/x');

    try {
        $transport->send($request);
        $this->fail('Expected ApiConnectionException on a refused connection.');
    } catch (ApiConnectionException $e) {
        expect($e->failurePhase)->toBe(FailurePhase::ConnectionNotEstablished);
        expect($e->curlErrno)->toBe(7); // CURLE_COULDNT_CONNECT
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
