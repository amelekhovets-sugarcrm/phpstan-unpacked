<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc;

use PHPStan\DependencyInjection\Container;
class LazyTypeNodeResolverExtensionRegistryProvider implements \PHPStan\PhpDoc\TypeNodeResolverExtensionRegistryProvider
{
    /**
     * @var \PHPStan\DependencyInjection\Container
     */
    private $container;
    /**
     * @var \PHPStan\PhpDoc\TypeNodeResolverExtensionRegistry|null
     */
    private $registry;
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    public function getRegistry() : \PHPStan\PhpDoc\TypeNodeResolverExtensionRegistry
    {
        if ($this->registry === null) {
            $this->registry = new \PHPStan\PhpDoc\TypeNodeResolverExtensionAwareRegistry($this->container->getByType(\PHPStan\PhpDoc\TypeNodeResolver::class), $this->container->getServicesByTag(\PHPStan\PhpDoc\TypeNodeResolverExtension::EXTENSION_TAG));
        }
        return $this->registry;
    }
}
