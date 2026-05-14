<?php

declare(strict_types=1);

use Nfe\Client;
use Nfe\Config;
use Nfe\Http\Response;
use Nfe\Http\RetryPolicy;
use Nfe\Resource\Dto\LegalPeople\LegalPerson;
use Nfe\Resource\Dto\Webhooks\Webhook;
use Nfe\Tests\Support\MockTransport;

function buildEntityClient(MockTransport $mock): Client
{
    return new Client(config: new Config(apiKey: 'k', retry: RetryPolicy::none(), transport: $mock));
}

it('LegalPeople create unwraps legalPeople envelope', function (): void {
    $mock = (new MockTransport())->push(new Response(
        201,
        [],
        '{"legalPeople":{"id":"lp-1","name":"Empresa","federalTaxNumber":12345678000190}}',
    ));
    $result = buildEntityClient($mock)->legalPeople->create('co-1', ['name' => 'Empresa']);

    expect($result)->toBeInstanceOf(LegalPerson::class);
    expect($result->id)->toBe('lp-1');
    expect($result->federalTaxNumber)->toBe(12345678000190);
});

it('LegalPeople list hits the right path', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '{"legalPeople":[]}'));
    buildEntityClient($mock)->legalPeople->list('co-1');

    expect($mock->lastRequest()?->path)->toBe('/v1/companies/co-1/legalpeople');
});

it('LegalPeople findByTaxNumber matches digits-only', function (): void {
    $mock = (new MockTransport())->push(new Response(
        200,
        [],
        '{"legalPeople":[{"id":"a","federalTaxNumber":11111111111111},{"id":"b","federalTaxNumber":22222222222222}]}',
    ));
    $result = buildEntityClient($mock)->legalPeople->findByTaxNumber('co-1', '22.222.222/2222-22');
    expect($result?->id)->toBe('b');
});

it('NaturalPeople routes correctly', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '{"naturalPeople":[]}'));
    buildEntityClient($mock)->naturalPeople->list('co-1');

    expect($mock->lastRequest()?->path)->toBe('/v1/companies/co-1/naturalpeople');
});

it('Webhooks routes to /v1/companies/{id}/webhooks', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '{"webhooks":[]}'));
    buildEntityClient($mock)->webhooks->list('co-1');

    $sent = $mock->lastRequest();
    expect($sent?->baseUrl)->toBe('https://api.nfe.io');
    expect($sent?->path)->toBe('/v1/companies/co-1/webhooks');
});

it('Webhooks create returns hydrated Webhook DTO', function (): void {
    $mock = (new MockTransport())->push(new Response(
        201,
        [],
        '{"id":"wh-1","url":"https://example.com/webhook","events":["invoice.issued"]}',
    ));
    $hook = buildEntityClient($mock)->webhooks->create('co-1', [
        'url' => 'https://example.com/webhook',
        'events' => ['invoice.issued'],
    ]);

    expect($hook)->toBeInstanceOf(Webhook::class);
    expect($hook->id)->toBe('wh-1');
    expect($hook->events)->toBe(['invoice.issued']);
});

it('Webhooks getAvailableEvents returns the 7 hard-coded events', function (): void {
    $events = buildEntityClient(new MockTransport())->webhooks->getAvailableEvents();
    expect($events)->toHaveCount(7);
    expect($events)->toContain('invoice.issued');
    expect($events)->toContain('company.created');
});

it('Webhooks test issues POST', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], '{"success":true}'));
    $result = buildEntityClient($mock)->webhooks->test('co-1', 'wh-1');

    expect($result['success'] ?? null)->toBeTrue();
    expect($mock->lastRequest()?->method)->toBe('POST');
});
