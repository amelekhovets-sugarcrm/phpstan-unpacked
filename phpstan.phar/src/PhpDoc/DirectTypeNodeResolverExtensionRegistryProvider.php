<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc;

class DirectTypeNodeResolverExtensionRegistryProvider implements \PHPStan\PhpDoc\TypeNodeResolverExtensionRegistryProvider
{
    /**
     * @var \PHPStan\PhpDoc\TypeNodeResolverExtensionRegistry
     */
    private $registry;
    public function __construct(\PHPStan\PhpDoc\TypeNodeResolverExtensionRegistry $registry)
    {
        $this->registry = $registry;
    }
    public function getRegistry() : \PHPStan\PhpDoc\TypeNodeResolverExtensionRegistry
    {
        return $this->registry;
    }
}
