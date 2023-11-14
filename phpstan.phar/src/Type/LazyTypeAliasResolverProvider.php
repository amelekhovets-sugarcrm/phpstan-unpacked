<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\DependencyInjection\Container;
class LazyTypeAliasResolverProvider implements \PHPStan\Type\TypeAliasResolverProvider
{
    /**
     * @var \PHPStan\DependencyInjection\Container
     */
    private $container;
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    public function getTypeAliasResolver() : \PHPStan\Type\TypeAliasResolver
    {
        return $this->container->getByType(\PHPStan\Type\TypeAliasResolver::class);
    }
}
