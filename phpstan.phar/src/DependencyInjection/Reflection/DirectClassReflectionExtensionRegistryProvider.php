<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection\Reflection;

use PHPStan\Broker\Broker;
use PHPStan\Reflection\AllowedSubTypesClassReflectionExtension;
use PHPStan\Reflection\ClassReflectionExtensionRegistry;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
/**
 * @internal
 */
class DirectClassReflectionExtensionRegistryProvider implements \PHPStan\DependencyInjection\Reflection\ClassReflectionExtensionRegistryProvider
{
    /**
     * @var PropertiesClassReflectionExtension[]
     */
    private $propertiesClassReflectionExtensions;
    /**
     * @var MethodsClassReflectionExtension[]
     */
    private $methodsClassReflectionExtensions;
    /**
     * @var AllowedSubTypesClassReflectionExtension[]
     */
    private $allowedSubTypesClassReflectionExtensions;
    /**
     * @var \PHPStan\Broker\Broker
     */
    private $broker;
    /**
     * @param PropertiesClassReflectionExtension[] $propertiesClassReflectionExtensions
     * @param MethodsClassReflectionExtension[] $methodsClassReflectionExtensions
     * @param AllowedSubTypesClassReflectionExtension[] $allowedSubTypesClassReflectionExtensions
     */
    public function __construct(array $propertiesClassReflectionExtensions, array $methodsClassReflectionExtensions, array $allowedSubTypesClassReflectionExtensions)
    {
        $this->propertiesClassReflectionExtensions = $propertiesClassReflectionExtensions;
        $this->methodsClassReflectionExtensions = $methodsClassReflectionExtensions;
        $this->allowedSubTypesClassReflectionExtensions = $allowedSubTypesClassReflectionExtensions;
    }
    public function setBroker(Broker $broker) : void
    {
        $this->broker = $broker;
    }
    public function addPropertiesClassReflectionExtension(PropertiesClassReflectionExtension $extension) : void
    {
        $this->propertiesClassReflectionExtensions[] = $extension;
    }
    public function addMethodsClassReflectionExtension(MethodsClassReflectionExtension $extension) : void
    {
        $this->methodsClassReflectionExtensions[] = $extension;
    }
    public function addAllowedSubTypesClassReflectionExtension(AllowedSubTypesClassReflectionExtension $extension) : void
    {
        $this->allowedSubTypesClassReflectionExtensions[] = $extension;
    }
    public function getRegistry() : ClassReflectionExtensionRegistry
    {
        return new ClassReflectionExtensionRegistry($this->broker, $this->propertiesClassReflectionExtensions, $this->methodsClassReflectionExtensions, $this->allowedSubTypesClassReflectionExtensions);
    }
}
