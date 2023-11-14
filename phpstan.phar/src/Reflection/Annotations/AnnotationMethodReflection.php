<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Annotations;

use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionVariantWithPhpDocs;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
class AnnotationMethodReflection implements ExtendedMethodReflection
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var \PHPStan\Reflection\ClassReflection
     */
    private $declaringClass;
    /**
     * @var \PHPStan\Type\Type
     */
    private $returnType;
    /**
     * @var AnnotationsMethodParameterReflection[]
     */
    private $parameters;
    /**
     * @var bool
     */
    private $isStatic;
    /**
     * @var bool
     */
    private $isVariadic;
    /**
     * @var \PHPStan\Type\Type|null
     */
    private $throwType;
    /** @var FunctionVariantWithPhpDocs[]|null */
    private $variants;
    /**
     * @param AnnotationsMethodParameterReflection[] $parameters
     */
    public function __construct(string $name, ClassReflection $declaringClass, Type $returnType, array $parameters, bool $isStatic, bool $isVariadic, ?Type $throwType)
    {
        $this->name = $name;
        $this->declaringClass = $declaringClass;
        $this->returnType = $returnType;
        $this->parameters = $parameters;
        $this->isStatic = $isStatic;
        $this->isVariadic = $isVariadic;
        $this->throwType = $throwType;
    }
    public function getDeclaringClass() : ClassReflection
    {
        return $this->declaringClass;
    }
    public function getPrototype() : ClassMemberReflection
    {
        return $this;
    }
    public function isStatic() : bool
    {
        return $this->isStatic;
    }
    public function isPrivate() : bool
    {
        return \false;
    }
    public function isPublic() : bool
    {
        return \true;
    }
    public function getName() : string
    {
        return $this->name;
    }
    public function getVariants() : array
    {
        if ($this->variants === null) {
            $this->variants = [new FunctionVariantWithPhpDocs(TemplateTypeMap::createEmpty(), null, $this->parameters, $this->isVariadic, $this->returnType, $this->returnType, new MixedType())];
        }
        return $this->variants;
    }
    public function isDeprecated() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function getDeprecatedDescription() : ?string
    {
        return null;
    }
    public function isFinal() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isInternal() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function getThrowType() : ?Type
    {
        return $this->throwType;
    }
    public function hasSideEffects() : TrinaryLogic
    {
        if ($this->returnType->isVoid()->yes()) {
            return TrinaryLogic::createYes();
        }
        return TrinaryLogic::createMaybe();
    }
    public function getDocComment() : ?string
    {
        return null;
    }
    public function getAsserts() : Assertions
    {
        return Assertions::createEmpty();
    }
    public function getSelfOutType() : ?Type
    {
        return null;
    }
    public function returnsByReference() : TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
}