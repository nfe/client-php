<?php

declare(strict_types=1);

use Nfe\Http\Response;

it('looks up headers case-insensitively', function () {
    $r = new Response(statusCode: 200, headers: ['location' => '/v1/x', 'content-type' => 'application/json'], body: '');
    expect($r->header('Location'))->toBe('/v1/x');
    expect($r->header('CONTENT-TYPE'))->toBe('application/json');
    expect($r->header('missing'))->toBeNull();
});

it('reports 2xx as success', function () {
    expect((new Response(200, [], ''))->isSuccess())->toBeTrue();
    expect((new Response(201, [], ''))->isSuccess())->toBeTrue();
    expect((new Response(202, [], ''))->isSuccess())->toBeTrue();
    expect((new Response(299, [], ''))->isSuccess())->toBeTrue();
});

it('reports non-2xx as failure', function () {
    expect((new Response(199, [], ''))->isSuccess())->toBeFalse();
    expect((new Response(300, [], ''))->isSuccess())->toBeFalse();
    expect((new Response(404, [], ''))->isSuccess())->toBeFalse();
    expect((new Response(500, [], ''))->isSuccess())->toBeFalse();
});
