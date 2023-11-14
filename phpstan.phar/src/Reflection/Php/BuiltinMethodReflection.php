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
interface BuiltinMethodReflection
{
    public function getName() : string;
    public function getReflection() : ?ReflectionMethod;
    public function getFileName() : ?string;
    /**
     * @return \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum
     */
    public function getDeclaringClass();
    public function getStartLine() : ?int;
    public function getEndLine() : ?int;
    public function getDocComment() : ?string;
    public function isStatic() : bool;
    public function isPrivate() : bool;
    public function isPublic() : bool;
    public function getPrototype() : self;
    public function isDeprecated() : TrinaryLogic;
    public function isVariadic() : bool;
    /**
     * @return \PHPStan\BetterReflection\Reflection\Adapter\ReflectionIntersectionType|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionNamedType|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionUnionType|null
     */
    public function getReturnType();
    /**
     * @return \PHPStan\BetterReflection\Reflection\Adapter\ReflectionIntersectionType|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionNamedType|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionUnionType|null
     */
    public function getTentativeReturnType();
    /**
     * @return ReflectionParameter[]
     */
    public function getParameters() : array;
    public function isFinal() : bool;
    public function isInternal() : bool;
    public function isAbstract() : bool;
    public function returnsByReference() : TrinaryLogic;
}
