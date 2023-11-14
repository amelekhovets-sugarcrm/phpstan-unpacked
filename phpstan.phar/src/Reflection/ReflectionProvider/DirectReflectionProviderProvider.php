<?php

declare (strict_types=1);
namespace PHPStan\Reflection\ReflectionProvider;

use PHPStan\Reflection\ReflectionProvider;
class DirectReflectionProviderProvider implements \PHPStan\Reflection\ReflectionProvider\ReflectionProviderProvider
{
    /**
     * @var \PHPStan\Reflection\ReflectionProvider
     */
    private $reflectionProvider;
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }
    public function getReflectionProvider() : ReflectionProvider
    {
        return $this->reflectionProvider;
    }
}
