<?php

declare(strict_types=1);

namespace Nfe\Tests\Support;

use LogicException;
use Nfe\Exception\ApiConnectionException;
use Nfe\Http\Request;
use Nfe\Http\Response;
use Nfe\Http\Transport;

/**
 * Deterministic transport for tests.
 *
 * Push pre-built {@see Response} objects (or {@see ApiConnectionException}
 * instances to simulate network failures) onto the queue with
 * {@see self::push()}, then exercise the SDK; the transport returns / throws
 * them in FIFO order and records the requests it received.
 */
final class MockTransport implements Transport
{
    /** @var list<Response|ApiConnectionException> */
    private array $queue = [];

    /** @var list<Request> */
    private array $sent = [];

    public function push(Response|ApiConnectionException $next): self
    {
        $this->queue[] = $next;
        return $this;
    }

    public function send(Request $request): Response
    {
        $this->sent[] = $request;

        if ($this->queue === []) {
            throw new LogicException('MockTransport queue is empty; push a Response or exception before sending.');
        }

        $next = array_shift($this->queue);

        if ($next instanceof ApiConnectionException) {
            throw $next;
        }

        return $next;
    }

    /**
     * @return list<Request>
     */
    public function sent(): array
    {
        return $this->sent;
    }

    public function lastRequest(): ?Request
    {
        $count = count($this->sent);
        return $count > 0 ? $this->sent[$count - 1] : null;
    }
}
