<?php

declare (strict_types=1);
namespace PHPStan\Analyser\ResultCache;

interface ResultCacheManagerFactory
{
    public function create() : \PHPStan\Analyser\ResultCache\ResultCacheManager;
}
