<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Type;
use function array_map;
class UnionTypeUnresolvedMethodPrototypeReflection implements \PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection
{
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var UnresolvedMethodPrototypeReflection[]
     */
    private $methodPrototypes;
    /**
     * @var \PHPStan\Reflection\ExtendedMethodReflection|null
     */
    private $transformedMethod;
    /**
     * @var \PHPStan\Reflection\Type\UnionTypeUnresolvedMethodPrototypeReflection|null
     */
    private $cachedDoNotResolveTemplateTypeMapToBounds;
    /**
     * @param UnresolvedMethodPrototypeReflection[] $methodPrototypes
     */
    public function __construct(string $methodName, array $methodPrototypes)
    {
        $this->methodName = $methodName;
        $this->methodPrototypes = $methodPrototypes;
    }
    public function doNotResolveTemplateTypeMapToBounds() : \PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection
    {
        if ($this->cachedDoNotResolveTemplateTypeMapToBounds !== null) {
            return $this->cachedDoNotResolveTemplateTypeMapToBounds;
        }
        return $this->cachedDoNotResolveTemplateTypeMapToBounds = new self($this->methodName, array_map(static function (\PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection $prototype) : \PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection {
            return $prototype->doNotResolveTemplateTypeMapToBounds();
        }, $this->methodPrototypes));
    }
    public function getNakedMethod() : ExtendedMethodReflection
    {
        return $this->getTransformedMethod();
    }
    public function getTransformedMethod() : ExtendedMethodReflection
    {
        if ($this->transformedMethod !== null) {
            return $this->transformedMethod;
        }
        $methods = array_map(static function (\PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection $prototype) : MethodReflection {
            return $prototype->getTransformedMethod();
        }, $this->methodPrototypes);
        return $this->transformedMethod = new \PHPStan\Reflection\Type\UnionTypeMethodReflection($this->methodName, $methods);
    }
    public function withCalledOnType(Type $type) : \PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection
    {
        return new self($this->methodName, array_map(static function (\PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection $prototype) use($type) : \PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection {
            return $prototype->withCalledOnType($type);
        }, $this->methodPrototypes));
    }
}
