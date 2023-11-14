<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Broker\Broker;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\ReflectionProvider;
use function array_merge;
use function strtolower;
class DynamicReturnTypeExtensionRegistry
{
    /**
     * @var \PHPStan\Reflection\ReflectionProvider
     */
    private $reflectionProvider;
    /**
     * @var DynamicMethodReturnTypeExtension[]
     */
    private $dynamicMethodReturnTypeExtensions;
    /**
     * @var DynamicStaticMethodReturnTypeExtension[]
     */
    private $dynamicStaticMethodReturnTypeExtensions;
    /**
     * @var DynamicFunctionReturnTypeExtension[]
     */
    private $dynamicFunctionReturnTypeExtensions;
    /** @var DynamicMethodReturnTypeExtension[][]|null */
    private $dynamicMethodReturnTypeExtensionsByClass;
    /** @var DynamicStaticMethodReturnTypeExtension[][]|null */
    private $dynamicStaticMethodReturnTypeExtensionsByClass;
    /**
     * @param DynamicMethodReturnTypeExtension[] $dynamicMethodReturnTypeExtensions
     * @param DynamicStaticMethodReturnTypeExtension[] $dynamicStaticMethodReturnTypeExtensions
     * @param DynamicFunctionReturnTypeExtension[] $dynamicFunctionReturnTypeExtensions
     */
    public function __construct(Broker $broker, ReflectionProvider $reflectionProvider, array $dynamicMethodReturnTypeExtensions, array $dynamicStaticMethodReturnTypeExtensions, array $dynamicFunctionReturnTypeExtensions)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->dynamicMethodReturnTypeExtensions = $dynamicMethodReturnTypeExtensions;
        $this->dynamicStaticMethodReturnTypeExtensions = $dynamicStaticMethodReturnTypeExtensions;
        $this->dynamicFunctionReturnTypeExtensions = $dynamicFunctionReturnTypeExtensions;
        foreach (array_merge($dynamicMethodReturnTypeExtensions, $dynamicStaticMethodReturnTypeExtensions, $dynamicFunctionReturnTypeExtensions) as $extension) {
            if (!$extension instanceof BrokerAwareExtension) {
                continue;
            }
            $extension->setBroker($broker);
        }
    }
    /**
     * @return DynamicMethodReturnTypeExtension[]
     */
    public function getDynamicMethodReturnTypeExtensionsForClass(string $className) : array
    {
        if ($this->dynamicMethodReturnTypeExtensionsByClass === null) {
            $byClass = [];
            foreach ($this->dynamicMethodReturnTypeExtensions as $extension) {
                $byClass[strtolower($extension->getClass())][] = $extension;
            }
            $this->dynamicMethodReturnTypeExtensionsByClass = $byClass;
        }
        return $this->getDynamicExtensionsForType($this->dynamicMethodReturnTypeExtensionsByClass, $className);
    }
    /**
     * @return DynamicStaticMethodReturnTypeExtension[]
     */
    public function getDynamicStaticMethodReturnTypeExtensionsForClass(string $className) : array
    {
        if ($this->dynamicStaticMethodReturnTypeExtensionsByClass === null) {
            $byClass = [];
            foreach ($this->dynamicStaticMethodReturnTypeExtensions as $extension) {
                $byClass[strtolower($extension->getClass())][] = $extension;
            }
            $this->dynamicStaticMethodReturnTypeExtensionsByClass = $byClass;
        }
        return $this->getDynamicExtensionsForType($this->dynamicStaticMethodReturnTypeExtensionsByClass, $className);
    }
    /**
     * @param DynamicMethodReturnTypeExtension[][]|DynamicStaticMethodReturnTypeExtension[][] $extensions
     * @return mixed[]
     */
    private function getDynamicExtensionsForType(array $extensions, string $className) : array
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return [];
        }
        $extensionsForClass = [[]];
        $class = $this->reflectionProvider->getClass($className);
        foreach (array_merge([$className], $class->getParentClassesNames(), $class->getNativeReflection()->getInterfaceNames()) as $extensionClassName) {
            $extensionClassName = strtolower($extensionClassName);
            if (!isset($extensions[$extensionClassName])) {
                continue;
            }
            $extensionsForClass[] = $extensions[$extensionClassName];
        }
        return array_merge(...$extensionsForClass);
    }
    /**
     * @return DynamicFunctionReturnTypeExtension[]
     */
    public function getDynamicFunctionReturnTypeExtensions() : array
    {
        return $this->dynamicFunctionReturnTypeExtensions;
    }
}
