<?php

declare(strict_types=1);

use Nfe\Exception\ApiConnectionException;
use Nfe\Http\FailurePhase;
use Nfe\Http\Psr18Transport;
use Nfe\Http\Request;
use Nfe\Tests\Support\NullPsrRequestFactory;
use Nfe\Tests\Support\NullPsrStreamFactory;
use Nfe\Tests\Support\ThrowingPsr18Client;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;

function buildPsr18(ClientExceptionInterface $toThrow): Psr18Transport
{
    return new Psr18Transport(
        new ThrowingPsr18Client($toThrow),
        new NullPsrRequestFactory(),
        new NullPsrStreamFactory(),
    );
}

it('maps a NetworkExceptionInterface to ConnectionNotEstablished', function (): void {
    $networkError = new class ('could not connect') extends RuntimeException implements NetworkExceptionInterface {
        public function getRequest(): RequestInterface
        {
            throw new LogicException('not needed in test');
        }
    };

    $transport = buildPsr18($networkError);

    try {
        $transport->send(new Request('GET', 'https://api.nfe.io', '/v1/x'));
        test()->fail('Expected ApiConnectionException.');
    } catch (ApiConnectionException $e) {
        expect($e->failurePhase)->toBe(FailurePhase::ConnectionNotEstablished);
    }
});

it('maps a generic ClientExceptionInterface to RequestMaybeSent', function (): void {
    $genericError = new class ('response parse failed') extends RuntimeException implements ClientExceptionInterface {};

    $transport = buildPsr18($genericError);

    try {
        $transport->send(new Request('POST', 'https://api.nfe.io', '/v1/x'));
        test()->fail('Expected ApiConnectionException.');
    } catch (ApiConnectionException $e) {
        expect($e->failurePhase)->toBe(FailurePhase::RequestMaybeSent);
    }
});
