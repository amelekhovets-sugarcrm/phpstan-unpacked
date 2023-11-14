<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\Type;
class ResolvedMethodReflection implements \PHPStan\Reflection\ExtendedMethodReflection
{
    /**
     * @var \PHPStan\Reflection\ExtendedMethodReflection
     */
    private $reflection;
    /**
     * @var \PHPStan\Type\Generic\TemplateTypeMap
     */
    private $resolvedTemplateTypeMap;
    /**
     * @var \PHPStan\Type\Generic\TemplateTypeVarianceMap
     */
    private $callSiteVarianceMap;
    /** @var ParametersAcceptorWithPhpDocs[]|null */
    private $variants;
    /**
     * @var \PHPStan\Reflection\Assertions|null
     */
    private $asserts;
    /**
     * @var \PHPStan\Type\Type|false|null
     */
    private $selfOutType = \false;
    public function __construct(\PHPStan\Reflection\ExtendedMethodReflection $reflection, TemplateTypeMap $resolvedTemplateTypeMap, TemplateTypeVarianceMap $callSiteVarianceMap)
    {
        $this->reflection = $reflection;
        $this->resolvedTemplateTypeMap = $resolvedTemplateTypeMap;
        $this->callSiteVarianceMap = $callSiteVarianceMap;
    }
    public function getName() : string
    {
        return $this->reflection->getName();
    }
    public function getPrototype() : \PHPStan\Reflection\ClassMemberReflection
    {
        return $this->reflection->getPrototype();
    }
    public function getVariants() : array
    {
        $variants = $this->variants;
        if ($variants !== null) {
            return $variants;
        }
        $variants = [];
        foreach ($this->reflection->getVariants() as $variant) {
            $variants[] = new \PHPStan\Reflection\ResolvedFunctionVariant($variant, $this->resolvedTemplateTypeMap, $this->callSiteVarianceMap, []);
        }
        $this->variants = $variants;
        return $variants;
    }
    public function getDeclaringClass() : \PHPStan\Reflection\ClassReflection
    {
        return $this->reflection->getDeclaringClass();
    }
    public function getDeclaringTrait() : ?\PHPStan\Reflection\ClassReflection
    {
        if ($this->reflection instanceof PhpMethodReflection) {
            return $this->reflection->getDeclaringTrait();
        }
        return null;
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
    public function getAsserts() : \PHPStan\Reflection\Assertions
    {
        return $this->asserts = $this->asserts ?? $this->reflection->getAsserts()->mapTypes(function (Type $type) {
            return TemplateTypeHelper::resolveTemplateTypes($type, $this->resolvedTemplateTypeMap, $this->callSiteVarianceMap, TemplateTypeVariance::createInvariant());
        });
    }
    public function getSelfOutType() : ?Type
    {
        if ($this->selfOutType === \false) {
            $selfOutType = $this->reflection->getSelfOutType();
            if ($selfOutType !== null) {
                $selfOutType = TemplateTypeHelper::resolveTemplateTypes($selfOutType, $this->resolvedTemplateTypeMap, $this->callSiteVarianceMap, TemplateTypeVariance::createInvariant());
            }
            $this->selfOutType = $selfOutType;
        }
        return $this->selfOutType;
    }
    public function returnsByReference() : TrinaryLogic
    {
        return $this->reflection->returnsByReference();
    }
}
