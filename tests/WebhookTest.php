<?php

declare(strict_types=1);

use Nfe\Exception\InvalidRequestException;
use Nfe\Exception\SignatureVerificationException;
use Nfe\Webhook;
use Nfe\WebhookEvent;

function makeSig(string $payload, string $secret, string $algo = 'sha1', bool $withPrefix = true): string
{
    $hex = hash_hmac($algo, $payload, $secret);
    return $withPrefix ? "{$algo}={$hex}" : $hex;
}

it('verifies a valid prefixed signature', function (): void {
    $payload = '{"hello":"world"}';
    $secret = 'shh';
    expect(Webhook::verifySignature($payload, makeSig($payload, $secret), $secret))->toBeTrue();
});

it('verifies a valid bare-hex signature (no prefix)', function (): void {
    $payload = '{"hello":"world"}';
    $secret = 'shh';
    expect(Webhook::verifySignature($payload, makeSig($payload, $secret, withPrefix: false), $secret))->toBeTrue();
});

it('rejects a wrong signature', function (): void {
    expect(Webhook::verifySignature('{"x":1}', 'sha1=deadbeef', 'shh'))->toBeFalse();
});

it('rejects an algorithm downgrade (header sha256, expected sha1)', function (): void {
    $payload = '{"x":1}';
    $secret = 'shh';
    $sha256 = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    expect(Webhook::verifySignature($payload, $sha256, $secret))->toBeFalse();
});

it('rejects empty signature', function (): void {
    expect(Webhook::verifySignature('payload', '', 'shh'))->toBeFalse();
});

it('rejects non-hex signature', function (): void {
    expect(Webhook::verifySignature('payload', 'sha1=not-hex-zzz', 'shh'))->toBeFalse();
});

it('constructs WebhookEvent from a v2 envelope', function (): void {
    $payload = json_encode([
        'action' => 'invoice.issued',
        'payload' => ['id' => 'inv-001', 'flowStatus' => 'Issued'],
    ]);
    $secret = 'shh';
    $sig = makeSig($payload, $secret);

    $event = Webhook::constructEvent($payload, $sig, $secret);

    expect($event)->toBeInstanceOf(WebhookEvent::class);
    expect($event->type)->toBe('invoice.issued');
    expect($event->data)->toBe(['id' => 'inv-001', 'flowStatus' => 'Issued']);
    expect($event->id)->toBe('inv-001');
});

it('constructs WebhookEvent from a flat payload with type field', function (): void {
    $payload = json_encode([
        'type' => 'company.created',
        'id' => 'evt-1',
        'companyId' => 'co-1',
    ]);
    $secret = 'shh';
    $sig = makeSig($payload, $secret);

    $event = Webhook::constructEvent($payload, $sig, $secret);
    expect($event->type)->toBe('company.created');
    expect($event->id)->toBe('evt-1');
});

it('raises SignatureVerificationException on invalid signature', function (): void {
    expect(fn() => Webhook::constructEvent('{"action":"x","payload":{}}', 'sha1=bad', 'shh'))
        ->toThrow(SignatureVerificationException::class);
});

it('raises InvalidRequestException on malformed JSON', function (): void {
    $payload = 'not-json{{{';
    $secret = 'shh';
    $sig = makeSig($payload, $secret);

    expect(fn() => Webhook::constructEvent($payload, $sig, $secret))
        ->toThrow(InvalidRequestException::class);
});

it('raises InvalidRequestException when no event type field is present', function (): void {
    $payload = json_encode(['some' => 'data', 'without' => 'type-field']);
    $secret = 'shh';
    $sig = makeSig($payload, $secret);

    expect(fn() => Webhook::constructEvent($payload, $sig, $secret))
        ->toThrow(InvalidRequestException::class);
});
