<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\Type;
use function array_map;
class UnionTypeUnresolvedPropertyPrototypeReflection implements \PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection
{
    /**
     * @var string
     */
    private $propertyName;
    /**
     * @var UnresolvedPropertyPrototypeReflection[]
     */
    private $propertyPrototypes;
    /**
     * @var \PHPStan\Reflection\PropertyReflection|null
     */
    private $transformedProperty;
    /**
     * @var \PHPStan\Reflection\Type\UnionTypeUnresolvedPropertyPrototypeReflection|null
     */
    private $cachedDoNotResolveTemplateTypeMapToBounds;
    /**
     * @param UnresolvedPropertyPrototypeReflection[] $propertyPrototypes
     */
    public function __construct(string $propertyName, array $propertyPrototypes)
    {
        $this->propertyName = $propertyName;
        $this->propertyPrototypes = $propertyPrototypes;
    }
    public function doNotResolveTemplateTypeMapToBounds() : \PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection
    {
        if ($this->cachedDoNotResolveTemplateTypeMapToBounds !== null) {
            return $this->cachedDoNotResolveTemplateTypeMapToBounds;
        }
        return $this->cachedDoNotResolveTemplateTypeMapToBounds = new self($this->propertyName, array_map(static function (\PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection $prototype) : \PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection {
            return $prototype->doNotResolveTemplateTypeMapToBounds();
        }, $this->propertyPrototypes));
    }
    public function getNakedProperty() : PropertyReflection
    {
        return $this->getTransformedProperty();
    }
    public function getTransformedProperty() : PropertyReflection
    {
        if ($this->transformedProperty !== null) {
            return $this->transformedProperty;
        }
        $methods = array_map(static function (\PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection $prototype) : PropertyReflection {
            return $prototype->getTransformedProperty();
        }, $this->propertyPrototypes);
        return $this->transformedProperty = new \PHPStan\Reflection\Type\UnionTypePropertyReflection($methods);
    }
    public function withFechedOnType(Type $type) : \PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection
    {
        return new self($this->propertyName, array_map(static function (\PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection $prototype) use($type) : \PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection {
            return $prototype->withFechedOnType($type);
        }, $this->propertyPrototypes));
    }
}
