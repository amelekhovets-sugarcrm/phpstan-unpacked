<?php

declare (strict_types=1);
namespace PHPStan\Collectors;

use PHPStan\DependencyInjection\Container;
class RegistryFactory
{
    /**
     * @var \PHPStan\DependencyInjection\Container
     */
    private $container;
    public const COLLECTOR_TAG = 'phpstan.collector';
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    public function create() : \PHPStan\Collectors\Registry
    {
        return new \PHPStan\Collectors\Registry($this->container->getServicesByTag(self::COLLECTOR_TAG));
    }
}
