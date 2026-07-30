<?php

declare(strict_types=1);

use Nfe\Client;
use Nfe\Config;
use Nfe\Environment;
use Nfe\Http\Response;
use Nfe\Http\RetryPolicy;
use Nfe\Tests\Support\MockTransport;

it('has exactly two cases', function (): void {
    expect(Environment::cases())->toHaveCount(2);
});

it('is string-backed with expected values', function (): void {
    expect(Environment::Production->value)->toBe('production');
    expect(Environment::Sandbox->value)->toBe('sandbox');
});

it('selecting Sandbox triggers E_USER_DEPRECATED; Production does not', function (): void {
    $deprecations = [];
    set_error_handler(function (int $errno, string $msg) use (&$deprecations): bool {
        $deprecations[] = $msg;
        return true;
    }, E_USER_DEPRECATED);

    try {
        new Config(apiKey: 'k', environment: Environment::Production);
        expect($deprecations)->toBeEmpty();

        new Config(apiKey: 'k', environment: Environment::Sandbox);
        expect($deprecations)->toHaveCount(1);
        expect($deprecations[0])->toContain('não existe host');
        expect($deprecations[0])->toContain('Development');
    } finally {
        restore_error_handler();
    }
});

it('Sandbox and Production emit identical URLs (no host routing by environment)', function (): void {
    // Pina a ausência de roteamento: não há host sandbox na plataforma
    // (confirmado no Node e na spec client-core) — o isolamento real é por
    // chave e por company.environment.
    $urls = [];
    set_error_handler(fn(): bool => true, E_USER_DEPRECATED); // o aviso é coberto pelo teste acima
    try {
        foreach ([Environment::Production, Environment::Sandbox] as $env) {
            $mock = (new MockTransport())->push(new Response(200, [], '{"companies":[]}'));
            $client = new Client(config: new Config(
                apiKey: 'k',
                environment: $env,
                retry: RetryPolicy::none(),
                transport: $mock,
            ));
            $client->companies->list();
            $urls[] = $mock->lastRequest()?->url();
        }
    } finally {
        restore_error_handler();
    }

    expect($urls[0])->toBe($urls[1]);
    expect($urls[0])->toStartWith('https://api.nfe.io');
});
