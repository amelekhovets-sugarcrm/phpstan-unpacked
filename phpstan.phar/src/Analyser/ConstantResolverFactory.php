<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\ReflectionProvider\ReflectionProviderProvider;
class ConstantResolverFactory
{
    /**
     * @var \PHPStan\Reflection\ReflectionProvider\ReflectionProviderProvider
     */
    private $reflectionProviderProvider;
    /**
     * @var \PHPStan\DependencyInjection\Container
     */
    private $container;
    public function __construct(ReflectionProviderProvider $reflectionProviderProvider, Container $container)
    {
        $this->reflectionProviderProvider = $reflectionProviderProvider;
        $this->container = $container;
    }
    public function create() : \PHPStan\Analyser\ConstantResolver
    {
        return new \PHPStan\Analyser\ConstantResolver($this->reflectionProviderProvider, $this->container->getParameter('dynamicConstantNames'));
    }
}
