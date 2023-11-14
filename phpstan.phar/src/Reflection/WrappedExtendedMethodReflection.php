<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PHPStan\Reflection\Php\DummyParameterWithPhpDocs;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use function array_map;
class WrappedExtendedMethodReflection implements \PHPStan\Reflection\ExtendedMethodReflection
{
    /**
     * @var \PHPStan\Reflection\MethodReflection
     */
    private $method;
    public function __construct(\PHPStan\Reflection\MethodReflection $method)
    {
        $this->method = $method;
    }
    public function getDeclaringClass() : \PHPStan\Reflection\ClassReflection
    {
        return $this->method->getDeclaringClass();
    }
    public function isStatic() : bool
    {
        return $this->method->isStatic();
    }
    public function isPrivate() : bool
    {
        return $this->method->isPrivate();
    }
    public function isPublic() : bool
    {
        return $this->method->isPublic();
    }
    public function getDocComment() : ?string
    {
        return $this->method->getDocComment();
    }
    public function getName() : string
    {
        return $this->method->getName();
    }
    public function getPrototype() : \PHPStan\Reflection\ClassMemberReflection
    {
        return $this->method->getPrototype();
    }
    public function getVariants() : array
    {
        $variants = [];
        foreach ($this->method->getVariants() as $variant) {
            if ($variant instanceof \PHPStan\Reflection\ParametersAcceptorWithPhpDocs) {
                $variants[] = $variant;
                continue;
            }
            $variants[] = new \PHPStan\Reflection\FunctionVariantWithPhpDocs($variant->getTemplateTypeMap(), $variant->getResolvedTemplateTypeMap(), array_map(static function (\PHPStan\Reflection\ParameterReflection $parameter) : \PHPStan\Reflection\ParameterReflectionWithPhpDocs {
                return $parameter instanceof \PHPStan\Reflection\ParameterReflectionWithPhpDocs ? $parameter : new DummyParameterWithPhpDocs($parameter->getName(), $parameter->getType(), $parameter->isOptional(), $parameter->passedByReference(), $parameter->isVariadic(), $parameter->getDefaultValue(), new MixedType(), $parameter->getType(), null);
            }, $variant->getParameters()), $variant->isVariadic(), $variant->getReturnType(), $variant->getReturnType(), new MixedType(), TemplateTypeVarianceMap::createEmpty());
        }
        return $variants;
    }
    public function isDeprecated() : TrinaryLogic
    {
        return $this->method->isDeprecated();
    }
    public function getDeprecatedDescription() : ?string
    {
        return $this->method->getDeprecatedDescription();
    }
    public function isFinal() : TrinaryLogic
    {
        return $this->method->isFinal();
    }
    public function isInternal() : TrinaryLogic
    {
        return $this->method->isInternal();
    }
    public function getThrowType() : ?Type
    {
        return $this->method->getThrowType();
    }
    public function hasSideEffects() : TrinaryLogic
    {
        return $this->method->hasSideEffects();
    }
    public function getAsserts() : \PHPStan\Reflection\Assertions
    {
        return \PHPStan\Reflection\Assertions::createEmpty();
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
