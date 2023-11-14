<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Php;

use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;
interface PhpMethodReflectionFactory
{
    /**
     * @param Type[] $phpDocParameterTypes
     * @param Type[] $phpDocParameterOutTypes
     */
    public function create(ClassReflection $declaringClass, ?ClassReflection $declaringTrait, \PHPStan\Reflection\Php\BuiltinMethodReflection $reflection, TemplateTypeMap $templateTypeMap, array $phpDocParameterTypes, ?Type $phpDocReturnType, ?Type $phpDocThrowType, ?string $deprecatedDescription, bool $isDeprecated, bool $isInternal, bool $isFinal, ?bool $isPure, Assertions $asserts, ?Type $selfOutType, ?string $phpDocComment, array $phpDocParameterOutTypes) : \PHPStan\Reflection\Php\PhpMethodReflection;
}