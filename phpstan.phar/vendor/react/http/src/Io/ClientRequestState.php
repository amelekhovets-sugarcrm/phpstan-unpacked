<?php

namespace _PHPStan_c6b09fbdf\React\Http\Io;

/** @internal */
class ClientRequestState
{
    /** @var int */
    public $numRequests = 0;
    /** @var ?\React\Promise\PromiseInterface */
    public $pending = null;
    /** @var ?\React\EventLoop\TimerInterface */
    public $timeout = null;
}