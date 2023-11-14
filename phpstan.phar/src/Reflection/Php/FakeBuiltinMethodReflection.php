<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Php;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionIntersectionType;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionNamedType;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionParameter;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionUnionType;
use PHPStan\TrinaryLogic;
use ReflectionException;
class FakeBuiltinMethodReflection implements \PHPStan\Reflection\Php\BuiltinMethodReflection
{
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum
     */
    private $declaringClass;
    /**
     * @param \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum $declaringClass
     */
    public function __construct(string $methodName, $declaringClass)
    {
        $this->methodName = $methodName;
        $this->declaringClass = $declaringClass;
    }
    public function getName() : string
    {
        return $this->methodName;
    }
    public function getReflection() : ?ReflectionMethod
    {
        return null;
    }
    public function getFileName() : ?string
    {
        return null;
    }
    /**
     * @return \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum
     */
    public function getDeclaringClass()
    {
        return $this->declaringClass;
    }
    public function getStartLine() : ?int
    {
        return null;
    }
    public function getEndLine() : ?int
    {
        return null;
    }
    public function getDocComment() : ?string
    {
        return null;
    }
    public function isStatic() : bool
    {
        return \false;
    }
    public function isPrivate() : bool
    {
        return \false;
    }
    public function isPublic() : bool
    {
        return \true;
    }
    public function getPrototype() : \PHPStan\Reflection\Php\BuiltinMethodReflection
    {
        throw new ReflectionException();
    }
    public function isDeprecated() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isVariadic() : bool
    {
        return \false;
    }
    public function isFinal() : bool
    {
        return \false;
    }
    public function isInternal() : bool
    {
        return \false;
    }
    public function isAbstract() : bool
    {
        return \false;
    }
    /**
     * @return \PHPStan\BetterReflection\Reflection\Adapter\ReflectionIntersectionType|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionNamedType|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionUnionType|null
     */
    public function getReturnType()
    {
        return null;
    }
    /**
     * @return \PHPStan\BetterReflection\Reflection\Adapter\ReflectionIntersectionType|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionNamedType|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionUnionType|null
     */
    public function getTentativeReturnType()
    {
        return null;
    }
    /**
     * @return ReflectionParameter[]
     */
    public function getParameters() : array
    {
        return [];
    }
    public function returnsByReference() : TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
}
