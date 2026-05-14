<?php

declare(strict_types=1);

namespace Nfe\Util;

/**
 * Helpers for working with the NFS-e flow status returned by the NFE.io API.
 *
 * The terminal statuses (the ones at which async invoice processing ends) match
 * the canonical Node SDK definition in `client-nodejs/src/core/types.ts:83-87`:
 *
 *     export const TERMINAL_FLOW_STATES: FlowStatus[] = [
 *       'Issued', 'IssueFailed', 'Cancelled', 'CancelFailed',
 *     ];
 *
 * Non-terminal statuses (the polling loop should continue when seeing these):
 * `PullFromCityHall`, `WaitingCalculateTaxes`, `WaitingDefineRpsNumber`,
 * `WaitingSend`, `WaitingSendCancel`, `WaitingReturn`, `WaitingDownload`.
 */
final class FlowStatus
{
    /** @var list<string> */
    public const TERMINAL = ['Issued', 'IssueFailed', 'Cancelled', 'CancelFailed'];

    /**
     * Whether the given flow status is a terminal state — i.e., the invoice has
     * reached a final outcome and polling should stop.
     */
    public static function isTerminal(string $status): bool
    {
        return in_array($status, self::TERMINAL, true);
    }
}
