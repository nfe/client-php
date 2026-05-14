<?php

declare(strict_types=1);

use Nfe\Http\Request;

it('builds a URL from baseUrl + path', function () {
    $r = new Request(method: 'GET', baseUrl: 'https://api.nfe.io', path: '/v1/companies');
    expect($r->url())->toBe('https://api.nfe.io/v1/companies');
});

it('appends query parameters', function () {
    $r = new Request(
        method:  'GET',
        baseUrl: 'https://api.nfe.io',
        path:    '/v1/companies',
        query:   ['pageCount' => 50, 'pageIndex' => 0],
    );
    expect($r->url())->toBe('https://api.nfe.io/v1/companies?pageCount=50&pageIndex=0');
});

it('preserves an existing question mark', function () {
    $r = new Request(
        method:  'GET',
        baseUrl: 'https://api.nfe.io',
        path:    '/v1/companies?foo=bar',
        query:   ['baz' => 'qux'],
    );
    expect($r->url())->toBe('https://api.nfe.io/v1/companies?foo=bar&baz=qux');
});

it('strips trailing slash from baseUrl', function () {
    $r = new Request(method: 'GET', baseUrl: 'https://api.nfe.io/', path: '/v1/x');
    expect($r->url())->toBe('https://api.nfe.io/v1/x');
});
