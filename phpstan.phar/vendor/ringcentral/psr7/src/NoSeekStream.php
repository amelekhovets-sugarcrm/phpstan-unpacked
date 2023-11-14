<?php

namespace _PHPStan_c6b09fbdf\RingCentral\Psr7;

use _PHPStan_c6b09fbdf\Psr\Http\Message\StreamInterface;
/**
 * Stream decorator that prevents a stream from being seeked
 */
class NoSeekStream extends StreamDecoratorTrait implements StreamInterface
{
    public function seek($offset, $whence = \SEEK_SET)
    {
        throw new \RuntimeException('Cannot seek a NoSeekStream');
    }
    public function isSeekable()
    {
        return \false;
    }
}
