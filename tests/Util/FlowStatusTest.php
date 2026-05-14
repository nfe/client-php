<?php

declare(strict_types=1);

use Nfe\Util\FlowStatus;

it('reports terminal states', function (): void {
    expect(FlowStatus::isTerminal('Issued'))->toBeTrue();
    expect(FlowStatus::isTerminal('IssueFailed'))->toBeTrue();
    expect(FlowStatus::isTerminal('Cancelled'))->toBeTrue();
    expect(FlowStatus::isTerminal('CancelFailed'))->toBeTrue();
});

it('reports non-terminal states', function (): void {
    expect(FlowStatus::isTerminal('PullFromCityHall'))->toBeFalse();
    expect(FlowStatus::isTerminal('WaitingCalculateTaxes'))->toBeFalse();
    expect(FlowStatus::isTerminal('WaitingDefineRpsNumber'))->toBeFalse();
    expect(FlowStatus::isTerminal('WaitingSend'))->toBeFalse();
    expect(FlowStatus::isTerminal('WaitingSendCancel'))->toBeFalse();
    expect(FlowStatus::isTerminal('WaitingReturn'))->toBeFalse();
    expect(FlowStatus::isTerminal('WaitingDownload'))->toBeFalse();
});

it('rejects unknown statuses as non-terminal (safe default)', function (): void {
    expect(FlowStatus::isTerminal('SomeFutureStatus'))->toBeFalse();
    expect(FlowStatus::isTerminal(''))->toBeFalse();
});
