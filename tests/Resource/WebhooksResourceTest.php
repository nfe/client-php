<?php

declare(strict_types=1);

use Nfe\Client;
use Nfe\Config;
use Nfe\Http\Response;
use Nfe\Http\RetryPolicy;
use Nfe\Resource\Dto\Webhooks\AccountWebhook;
use Nfe\Tests\Support\MockTransport;

/*
 * Cobertura dos métodos account-scoped (/v2/webhooks). As fixtures espelham o
 * transcript das sondas ao vivo (2026-07-02/03): envelope `webHook` nos dois
 * sentidos, contentType/status serializados como string.
 */

function buildWebhooksClient(MockTransport $mock): Client
{
    return new Client(config: new Config(apiKey: 'k', retry: RetryPolicy::none(), transport: $mock));
}

/** Fixture do 201 real: secret ecoado, contentType/status string. */
const ACCOUNT_WEBHOOK_CREATED_JSON = '{"webHook":{'
    . '"id":"e30cf7cc-a468-4edf-9a8d-8e5eae37cf01",'
    . '"uri":"https://example.com/hooks/nfe",'
    . '"contentType":"json",'
    . '"secret":"0123456789abcdef0123456789abcdef",'
    . '"insecureSsl":false,'
    . '"status":"Active",'
    . '"filters":["service_invoice.issued_successfully"],'
    . '"createdOn":"2026-07-02T18:22:10.55Z",'
    . '"modifiedOn":"2026-07-02T18:22:10.55Z"'
    . '}}';

it('createAccountWebhook wraps the body in the webHook envelope and unwraps the 201', function (): void {
    $mock = (new MockTransport())->push(new Response(201, [], ACCOUNT_WEBHOOK_CREATED_JSON));

    $hook = buildWebhooksClient($mock)->webhooks->createAccountWebhook([
        'uri'         => 'https://example.com/hooks/nfe',
        'contentType' => 'json',
        'secret'      => '0123456789abcdef0123456789abcdef',
        'filters'     => ['service_invoice.issued_successfully'],
    ]);

    $sent = $mock->lastRequest();
    expect($sent?->method)->toBe('POST');
    expect($sent?->baseUrl)->toBe('https://api.nfe.io');
    expect($sent?->path)->toBe('/v2/webhooks');

    $body = json_decode((string) $sent?->body, associative: true);
    expect($body)->toHaveKey('webHook');
    expect($body['webHook']['uri'])->toBe('https://example.com/hooks/nfe');

    expect($hook)->toBeInstanceOf(AccountWebhook::class);
    expect($hook->id)->toBe('e30cf7cc-a468-4edf-9a8d-8e5eae37cf01');
    expect($hook->secret)->toBe('0123456789abcdef0123456789abcdef');
    expect($hook->contentType)->toBe('json');
    expect($hook->status)->toBe('Active');
    expect($hook->raw)->toHaveKey('uri');
});

it('updateAccountWebhook sends the webHook envelope via PUT and unwraps the response', function (): void {
    $mock = (new MockTransport())->push(new Response(200, [], ACCOUNT_WEBHOOK_CREATED_JSON));

    $hook = buildWebhooksClient($mock)->webhooks->updateAccountWebhook('wh-1', [
        'uri'    => 'https://example.com/hooks/nfe',
        'status' => 'Active',
    ]);

    $sent = $mock->lastRequest();
    expect($sent?->method)->toBe('PUT');
    expect($sent?->path)->toBe('/v2/webhooks/wh-1');

    $body = json_decode((string) $sent?->body, associative: true);
    expect($body)->toHaveKey('webHook');
    expect($body['webHook']['status'])->toBe('Active');

    expect($hook)->toBeInstanceOf(AccountWebhook::class);
    expect($hook->status)->toBe('Active');
});

it('retrieveAccountWebhook unwraps the webHook envelope', function (): void {
    // Leitura real: secret OMITIDO.
    $mock = (new MockTransport())->push(new Response(
        200,
        [],
        '{"webHook":{"id":"wh-1","uri":"https://example.com/hooks/nfe","contentType":"json","status":"Active"}}',
    ));

    $hook = buildWebhooksClient($mock)->webhooks->retrieveAccountWebhook('wh-1');

    expect($mock->lastRequest()?->path)->toBe('/v2/webhooks/wh-1');
    expect($hook->id)->toBe('wh-1');
    expect($hook->secret)->toBeNull();
});

it('retrieveAccountWebhook falls back to a raw (non-enveloped) body', function (): void {
    $mock = (new MockTransport())->push(new Response(
        200,
        [],
        '{"id":"wh-1","uri":"https://example.com/hooks/nfe","status":"Active"}',
    ));

    $hook = buildWebhooksClient($mock)->webhooks->retrieveAccountWebhook('wh-1');

    expect($hook)->toBeInstanceOf(AccountWebhook::class);
    expect($hook->id)->toBe('wh-1');
    expect($hook->uri)->toBe('https://example.com/hooks/nfe');
});

it('listAccountWebhooks unwraps the webHooks envelope', function (): void {
    $mock = (new MockTransport())->push(new Response(
        200,
        [],
        '{"webHooks":[{"id":"wh-1","uri":"https://a.example"},{"id":"wh-2","uri":"https://b.example"}]}',
    ));

    $page = buildWebhooksClient($mock)->webhooks->listAccountWebhooks();

    expect($mock->lastRequest()?->path)->toBe('/v2/webhooks');
    expect($page->data)->toHaveCount(2);
    expect($page->data[0])->toBeInstanceOf(AccountWebhook::class);
    expect($page->data[1]->id)->toBe('wh-2');
});

it('deleteAccountWebhook targets the single-webhook path', function (): void {
    $mock = (new MockTransport())->push(new Response(204, [], ''));
    buildWebhooksClient($mock)->webhooks->deleteAccountWebhook('wh-1');

    $sent = $mock->lastRequest();
    expect($sent?->method)->toBe('DELETE');
    expect($sent?->path)->toBe('/v2/webhooks/wh-1');
});

it('deleteAllAccountWebhooks is a distinct method hitting the collection path', function (): void {
    $mock = (new MockTransport())->push(new Response(204, [], ''));
    buildWebhooksClient($mock)->webhooks->deleteAllAccountWebhooks();

    $sent = $mock->lastRequest();
    expect($sent?->method)->toBe('DELETE');
    expect($sent?->path)->toBe('/v2/webhooks');
});

it('pingAccountWebhook issues PUT /v2/webhooks/{id}/pings', function (): void {
    $mock = (new MockTransport())->push(new Response(204, [], ''));
    buildWebhooksClient($mock)->webhooks->pingAccountWebhook('wh-1');

    $sent = $mock->lastRequest();
    expect($sent?->method)->toBe('PUT');
    expect($sent?->path)->toBe('/v2/webhooks/wh-1/pings');
});

it('fetchEventTypes extracts the live event ids', function (): void {
    $mock = (new MockTransport())->push(new Response(
        200,
        [],
        '{"eventTypes":['
            . '{"id":"service_invoice.issued_successfully","resource":"service_invoice"},'
            . '{"id":"service_invoice.cancelled_error","resource":"service_invoice"},'
            . '{"id":"product_invoice.issued","resource":"product_invoice"}'
            . ']}',
    ));

    $ids = buildWebhooksClient($mock)->webhooks->fetchEventTypes();

    expect($mock->lastRequest()?->path)->toBe('/v2/webhooks/eventTypes');
    expect($ids)->toBe([
        'service_invoice.issued_successfully',
        'service_invoice.cancelled_error',
        'product_invoice.issued',
    ]);
});
