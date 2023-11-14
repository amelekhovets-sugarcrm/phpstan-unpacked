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
class NativeBuiltinMethodReflection implements \PHPStan\Reflection\Php\BuiltinMethodReflection
{
    /**
     * @var \PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod
     */
    private $reflection;
    public function __construct(ReflectionMethod $reflection)
    {
        $this->reflection = $reflection;
    }
    public function getName() : string
    {
        return $this->reflection->getName();
    }
    public function getReflection() : ?ReflectionMethod
    {
        return $this->reflection;
    }
    public function getFileName() : ?string
    {
        $fileName = $this->reflection->getFileName();
        if ($fileName === \false) {
            return null;
        }
        return $fileName;
    }
    /**
     * @return \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum
     */
    public function getDeclaringClass()
    {
        return $this->reflection->getDeclaringClass();
    }
    public function getStartLine() : ?int
    {
        $line = $this->reflection->getStartLine();
        if ($line === \false) {
            return null;
        }
        return $line;
    }
    public function getEndLine() : ?int
    {
        $line = $this->reflection->getEndLine();
        if ($line === \false) {
            return null;
        }
        return $line;
    }
    public function getDocComment() : ?string
    {
        $docComment = $this->reflection->getDocComment();
        if ($docComment === \false) {
            return null;
        }
        return $docComment;
    }
    public function isStatic() : bool
    {
        return $this->reflection->isStatic();
    }
    public function isPrivate() : bool
    {
        return $this->reflection->isPrivate();
    }
    public function isPublic() : bool
    {
        return $this->reflection->isPublic();
    }
    public function isConstructor() : bool
    {
        return $this->reflection->isConstructor();
    }
    public function getPrototype() : \PHPStan\Reflection\Php\BuiltinMethodReflection
    {
        return new self($this->reflection->getPrototype());
    }
    public function isDeprecated() : TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->reflection->isDeprecated());
    }
    public function isFinal() : bool
    {
        return $this->reflection->isFinal();
    }
    public function isInternal() : bool
    {
        return $this->reflection->isInternal();
    }
    public function isAbstract() : bool
    {
        return $this->reflection->isAbstract();
    }
    public function isVariadic() : bool
    {
        return $this->reflection->isVariadic();
    }
    /**
     * @return \PHPStan\BetterReflection\Reflection\Adapter\ReflectionIntersectionType|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionNamedType|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionUnionType|null
     */
    public function getReturnType()
    {
        return $this->reflection->getReturnType();
    }
    /**
     * @return \PHPStan\BetterReflection\Reflection\Adapter\ReflectionIntersectionType|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionNamedType|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionUnionType|null
     */
    public function getTentativeReturnType()
    {
        return $this->reflection->getTentativeReturnType();
    }
    /**
     * @return ReflectionParameter[]
     */
    public function getParameters() : array
    {
        return $this->reflection->getParameters();
    }
    public function returnsByReference() : TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->reflection->returnsReference());
    }
}
