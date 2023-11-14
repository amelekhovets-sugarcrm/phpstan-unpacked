<?php

namespace _PHPStan_c6b09fbdf\React\Http\Client;

use _PHPStan_c6b09fbdf\Psr\Http\Message\RequestInterface;
use _PHPStan_c6b09fbdf\React\Http\Io\ClientConnectionManager;
use _PHPStan_c6b09fbdf\React\Http\Io\ClientRequestStream;
/**
 * @internal
 */
class Client
{
    /** @var ClientConnectionManager */
    private $connectionManager;
    public function __construct(ClientConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }
    /** @return ClientRequestStream */
    public function request(RequestInterface $request)
    {
        return new ClientRequestStream($this->connectionManager, $request);
    }
}