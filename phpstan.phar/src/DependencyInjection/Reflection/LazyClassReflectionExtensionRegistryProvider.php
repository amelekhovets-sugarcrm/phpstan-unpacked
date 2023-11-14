<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection\Reflection;

use PHPStan\Broker\Broker;
use PHPStan\Broker\BrokerFactory;
use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\Annotations\AnnotationsMethodsClassReflectionExtension;
use PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension;
use PHPStan\Reflection\ClassReflectionExtensionRegistry;
use PHPStan\Reflection\Php\PhpClassReflectionExtension;
use function array_merge;
class LazyClassReflectionExtensionRegistryProvider implements \PHPStan\DependencyInjection\Reflection\ClassReflectionExtensionRegistryProvider
{
    /**
     * @var \PHPStan\DependencyInjection\Container
     */
    private $container;
    /**
     * @var \PHPStan\Reflection\ClassReflectionExtensionRegistry|null
     */
    private $registry;
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    public function getRegistry() : ClassReflectionExtensionRegistry
    {
        if ($this->registry === null) {
            $phpClassReflectionExtension = $this->container->getByType(PhpClassReflectionExtension::class);
            $annotationsMethodsClassReflectionExtension = $this->container->getByType(AnnotationsMethodsClassReflectionExtension::class);
            $annotationsPropertiesClassReflectionExtension = $this->container->getByType(AnnotationsPropertiesClassReflectionExtension::class);
            $this->registry = new ClassReflectionExtensionRegistry($this->container->getByType(Broker::class), array_merge([$phpClassReflectionExtension], $this->container->getServicesByTag(BrokerFactory::PROPERTIES_CLASS_REFLECTION_EXTENSION_TAG), [$annotationsPropertiesClassReflectionExtension]), array_merge([$phpClassReflectionExtension], $this->container->getServicesByTag(BrokerFactory::METHODS_CLASS_REFLECTION_EXTENSION_TAG), [$annotationsMethodsClassReflectionExtension]), $this->container->getServicesByTag(BrokerFactory::ALLOWED_SUB_TYPES_CLASS_REFLECTION_EXTENSION_TAG));
        }
        return $this->registry;
    }
}