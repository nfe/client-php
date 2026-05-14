<?php

declare(strict_types=1);

use Nfe\Util\UserAgent;
use Nfe\Version;

it('starts with the SDK identifier and version', function (): void {
    $ua = UserAgent::build();
    expect($ua)->toStartWith('Nfe-PHP/' . Version::CURRENT);
});

it('includes the PHP version', function (): void {
    expect(UserAgent::build())->toContain('php/' . PHP_VERSION);
});

it('appends the optional suffix', function (): void {
    $ua = UserAgent::build(suffix: 'WHMCS/8.10 nfeio-module/3.2.0');
    expect($ua)->toEndWith('WHMCS/8.10 nfeio-module/3.2.0');
});

it('omits the suffix when blank', function (): void {
    $ua = UserAgent::build(suffix: '');
    expect($ua)->not->toContain('  ');
});
