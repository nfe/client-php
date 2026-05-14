<?php

declare(strict_types=1);

use Nfe\Client;
use Nfe\Config;
use Nfe\Http\Response;
use Nfe\Http\RetryPolicy;
use Nfe\Resource\Dto\Companies\Company;
use Nfe\Tests\Support\MockTransport;

function buildCoClient(MockTransport $mock): Client
{
    return new Client(config: new Config(apiKey: 'k', retry: RetryPolicy::none(), transport: $mock));
}

it('Companies routes to api.nfe.io/v1', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '{"companies":{"id":"x","name":"Acme"}}'));
    buildCoClient($mock)->companies->retrieve('abc');

    $sent = $mock->lastRequest();
    expect($sent?->baseUrl)->toBe('https://api.nfe.io');
    expect($sent?->path)->toBe('/v1/companies/abc');
});

it('create unwraps the companies envelope', function (): void {
    $mock = (new MockTransport())->push(new Response(
        201,
        [],
        '{"companies":{"id":"co-1","name":"Acme","federalTaxNumber":12345678000190}}',
    ));
    $result = buildCoClient($mock)->companies->create(['name' => 'Acme']);

    expect($result)->toBeInstanceOf(Company::class);
    expect($result->id)->toBe('co-1');
    expect($result->federalTaxNumber)->toBe(12345678000190);
});

it('list hydrates the companies array', function (): void {
    $payload = json_encode([
        'companies' => [
            ['id' => 'a', 'name' => 'Acme'],
            ['id' => 'b', 'name' => 'Beta'],
        ],
        'pageIndex' => 0,
        'pageCount' => 100,
    ]);
    $mock = (new MockTransport())->push(new Response(200, [], (string) $payload));
    $list = buildCoClient($mock)->companies->list();

    expect($list->data)->toHaveCount(2);
    expect($list->data[0]->id)->toBe('a');
    expect($list->page->pageIndex)->toBe(0);
});

it('remove returns deleted=true on 2xx', function (): void {
    $mock = (new MockTransport())->push(new Response(204, [], ''));
    $result = buildCoClient($mock)->companies->remove('co-1');

    expect($result)->toBe(['deleted' => true, 'id' => 'co-1']);
});

it('getCertificateStatus computes daysUntilExpiration from expiresOn', function (): void {
    $future = (new DateTimeImmutable('+45 days'))->format('Y-m-d\TH:i:s\Z');
    $mock = (new MockTransport())->push(new Response(
        200,
        [],
        json_encode(['hasCertificate' => true, 'expiresOn' => $future, 'isValid' => true]),
    ));
    $status = buildCoClient($mock)->companies->getCertificateStatus('co-1');

    expect($status->hasCertificate)->toBeTrue();
    expect($status->daysUntilExpiration)->toBeGreaterThanOrEqual(44)->toBeLessThanOrEqual(46);
    expect($status->isExpiringSoon)->toBeFalse(); // 45 > default threshold 30
});

it('getCertificateStatus reports isExpiringSoon when within threshold', function (): void {
    $soon = (new DateTimeImmutable('+10 days'))->format('Y-m-d\TH:i:s\Z');
    $mock = (new MockTransport())->push(new Response(
        200,
        [],
        json_encode(['hasCertificate' => true, 'expiresOn' => $soon]),
    ));
    $status = buildCoClient($mock)->companies->getCertificateStatus('co-1', expiringSoonThreshold: 30);

    expect($status->isExpiringSoon)->toBeTrue();
});

it('getCertificateStatus handles no-certificate case', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '{"hasCertificate":false}'));
    $status = buildCoClient($mock)->companies->getCertificateStatus('co-1');

    expect($status->hasCertificate)->toBeFalse();
    expect($status->expiresOn)->toBeNull();
    expect($status->daysUntilExpiration)->toBeNull();
});
