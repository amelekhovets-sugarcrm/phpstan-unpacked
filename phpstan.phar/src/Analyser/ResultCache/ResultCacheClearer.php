<?php

declare (strict_types=1);
namespace PHPStan\Analyser\ResultCache;

use function dirname;
use function is_file;
use function unlink;
class ResultCacheClearer
{
    /**
     * @var string
     */
    private $cacheFilePath;
    public function __construct(string $cacheFilePath)
    {
        $this->cacheFilePath = $cacheFilePath;
    }
    public function clear() : string
    {
        $dir = dirname($this->cacheFilePath);
        if (!is_file($this->cacheFilePath)) {
            return $dir;
        }
        @unlink($this->cacheFilePath);
        return $dir;
    }
}
