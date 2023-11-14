<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Dummy;

use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
class ChangedTypeMethodReflection implements ExtendedMethodReflection
{
    /**
     * @var \PHPStan\Reflection\ClassReflection
     */
    private $declaringClass;
    /**
     * @var \PHPStan\Reflection\ExtendedMethodReflection
     */
    private $reflection;
    /**
     * @var ParametersAcceptorWithPhpDocs[]
     */
    private $variants;
    /**
     * @param ParametersAcceptorWithPhpDocs[] $variants
     */
    public function __construct(ClassReflection $declaringClass, ExtendedMethodReflection $reflection, array $variants)
    {
        $this->declaringClass = $declaringClass;
        $this->reflection = $reflection;
        $this->variants = $variants;
    }
    public function getDeclaringClass() : ClassReflection
    {
        return $this->declaringClass;
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
    public function getDocComment() : ?string
    {
        return $this->reflection->getDocComment();
    }
    public function getName() : string
    {
        return $this->reflection->getName();
    }
    public function getPrototype() : ClassMemberReflection
    {
        return $this->reflection->getPrototype();
    }
    public function getVariants() : array
    {
        return $this->variants;
    }
    public function isDeprecated() : TrinaryLogic
    {
        return $this->reflection->isDeprecated();
    }
    public function getDeprecatedDescription() : ?string
    {
        return $this->reflection->getDeprecatedDescription();
    }
    public function isFinal() : TrinaryLogic
    {
        return $this->reflection->isFinal();
    }
    public function isInternal() : TrinaryLogic
    {
        return $this->reflection->isInternal();
    }
    public function getThrowType() : ?Type
    {
        return $this->reflection->getThrowType();
    }
    public function hasSideEffects() : TrinaryLogic
    {
        return $this->reflection->hasSideEffects();
    }
    public function getAsserts() : Assertions
    {
        return $this->reflection->getAsserts();
    }
    public function getSelfOutType() : ?Type
    {
        return $this->reflection->getSelfOutType();
    }
    public function returnsByReference() : TrinaryLogic
    {
        return $this->reflection->returnsByReference();
    }
}
