<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PHPStan\Broker\Broker;
use function array_merge;
class ClassReflectionExtensionRegistry
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
     * @param PropertiesClassReflectionExtension[] $propertiesClassReflectionExtensions
     * @param MethodsClassReflectionExtension[] $methodsClassReflectionExtensions
     * @param AllowedSubTypesClassReflectionExtension[] $allowedSubTypesClassReflectionExtensions
     */
    public function __construct(Broker $broker, array $propertiesClassReflectionExtensions, array $methodsClassReflectionExtensions, array $allowedSubTypesClassReflectionExtensions)
    {
        $this->propertiesClassReflectionExtensions = $propertiesClassReflectionExtensions;
        $this->methodsClassReflectionExtensions = $methodsClassReflectionExtensions;
        $this->allowedSubTypesClassReflectionExtensions = $allowedSubTypesClassReflectionExtensions;
        foreach (array_merge($propertiesClassReflectionExtensions, $methodsClassReflectionExtensions, $allowedSubTypesClassReflectionExtensions) as $extension) {
            if (!$extension instanceof \PHPStan\Reflection\BrokerAwareExtension) {
                continue;
            }
            $extension->setBroker($broker);
        }
    }
    /**
     * @return PropertiesClassReflectionExtension[]
     */
    public function getPropertiesClassReflectionExtensions() : array
    {
        return $this->propertiesClassReflectionExtensions;
    }
    /**
     * @return MethodsClassReflectionExtension[]
     */
    public function getMethodsClassReflectionExtensions() : array
    {
        return $this->methodsClassReflectionExtensions;
    }
    /**
     * @return AllowedSubTypesClassReflectionExtension[]
     */
    public function getAllowedSubTypesClassReflectionExtensions() : array
    {
        return $this->allowedSubTypesClassReflectionExtensions;
    }
}
