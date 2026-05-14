<?php

declare(strict_types=1);

use Nfe\Client;
use Nfe\Config;
use Nfe\Http\Response;
use Nfe\Http\RetryPolicy;
use Nfe\Resource\AbstractResource;
use Nfe\Response\InvoiceResponse;
use Nfe\Response\Issued;
use Nfe\Response\Pending;
use Nfe\Tests\Support\MockTransport;
use Nfe\Exception\InvalidRequestException;

/**
 * @internal
 */
final class TestResource extends AbstractResource
{
    protected function apiFamily(): string
    {
        return 'core';
    }

    protected function apiVersion(): string
    {
        return 'v1';
    }

    public function doGet(string $path, array $query = []): Response
    {
        return $this->get($path, $query);
    }

    public function doPost(string $path, mixed $body = null): Response
    {
        return $this->post($path, $body);
    }

    public function exposeLocationParse(string $location): string
    {
        return $this->extractInvoiceIdFromLocation($location);
    }

    public function doAsync(Response $response): InvoiceResponse
    {
        return $this->handleAsyncResponse(
            $response,
            issuedDtoClass: TestInvoiceDto::class,
            issuedFactory: fn (TestInvoiceDto $dto) => new TestIssued($dto),
            pendingFactory: fn (string $id, string $loc) => new TestPending($id, $loc),
        );
    }
}

final readonly class TestInvoiceDto
{
    public function __construct(public string $id, public ?string $status = null) {}
}

final class TestPending implements Pending
{
    public function __construct(private readonly string $id, private readonly string $location) {}
    public function invoiceId(): string { return $this->id; }
    public function location(): string { return $this->location; }
}

final class TestIssued implements Issued
{
    public function __construct(private readonly TestInvoiceDto $dto) {}
    public function resource(): object { return $this->dto; }
}

function makeTestClient(MockTransport $mock): Client
{
    return new Client(config: new Config(
        apiKey: 'k',
        retry: RetryPolicy::none(),
        transport: $mock,
    ));
}

it('sends a GET to the resolved baseUrl + version + path', function () {
    $mock = (new MockTransport())->push(new Response(200, [], '{}'));
    $client = makeTestClient($mock);

    (new TestResource($client))->doGet('/companies');

    $sent = $mock->lastRequest();
    expect($sent?->baseUrl)->toBe('https://api.nfe.io');
    expect($sent?->path)->toBe('/v1/companies');
    expect($sent?->method)->toBe('GET');
});

it('encodes POST bodies as JSON', function () {
    $mock = (new MockTransport())->push(new Response(201, [], '{}'));
    $client = makeTestClient($mock);

    (new TestResource($client))->doPost('/x', ['name' => 'Acme', 'count' => 3]);

    $body = $mock->lastRequest()?->body;
    expect($body)->toBe('{"name":"Acme","count":3}');
});

it('returns Pending when API responds 202 with Location', function () {
    $resp = new Response(202, ['location' => '/v1/companies/abc/serviceinvoices/xyz123'], '');
    $client = makeTestClient(new MockTransport());

    $result = (new TestResource($client))->doAsync($resp);

    expect($result)->toBeInstanceOf(Pending::class);
    expect($result->invoiceId())->toBe('xyz123');
    expect($result->location())->toBe('/v1/companies/abc/serviceinvoices/xyz123');
});

it('throws when 202 arrives without Location', function () {
    $resp = new Response(202, [], '');
    $client = makeTestClient(new MockTransport());

    expect(fn () => (new TestResource($client))->doAsync($resp))
        ->toThrow(InvalidRequestException::class);
});

it('returns Issued when API responds 201 with body', function () {
    $resp = new Response(201, [], '{"id":"abc","status":"Issued"}');
    $client = makeTestClient(new MockTransport());

    $result = (new TestResource($client))->doAsync($resp);

    expect($result)->toBeInstanceOf(Issued::class);
    $dto = $result->resource();
    expect($dto)->toBeInstanceOf(TestInvoiceDto::class);
    expect($dto->id)->toBe('abc');
    expect($dto->status)->toBe('Issued');
});

it('extracts the invoice id from absolute Location URLs', function () {
    $client = makeTestClient(new MockTransport());
    $r = new TestResource($client);

    expect($r->exposeLocationParse('https://api.nfe.io/v1/companies/abc/serviceinvoices/xyz123'))
        ->toBe('xyz123');
    expect($r->exposeLocationParse('/v1/x/y'))->toBe('y');
});
