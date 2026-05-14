<?php

declare(strict_types=1);

use Nfe\Util\UserAgent;
use Nfe\Version;

it('starts with the SDK identifier and version', function () {
    $ua = UserAgent::build();
    expect($ua)->toStartWith('Nfe-PHP/' . Version::CURRENT);
});

it('includes the PHP version', function () {
    expect(UserAgent::build())->toContain('php/' . PHP_VERSION);
});

it('appends the optional suffix', function () {
    $ua = UserAgent::build(suffix: 'WHMCS/8.10 nfeio-module/3.2.0');
    expect($ua)->toEndWith('WHMCS/8.10 nfeio-module/3.2.0');
});

it('omits the suffix when blank', function () {
    $ua = UserAgent::build(suffix: '');
    expect($ua)->not->toContain('  ');
});
