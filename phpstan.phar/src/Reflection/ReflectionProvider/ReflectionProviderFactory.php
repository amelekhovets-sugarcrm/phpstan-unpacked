<?php

declare (strict_types=1);
namespace PHPStan\Reflection\ReflectionProvider;

use PHPStan\Reflection\ReflectionProvider;
class ReflectionProviderFactory
{
    /**
     * @var \PHPStan\Reflection\ReflectionProvider
     */
    private $staticReflectionProvider;
    public function __construct(ReflectionProvider $staticReflectionProvider)
    {
        $this->staticReflectionProvider = $staticReflectionProvider;
    }
    public function create() : ReflectionProvider
    {
        return new \PHPStan\Reflection\ReflectionProvider\MemoizingReflectionProvider($this->staticReflectionProvider);
    }
}
