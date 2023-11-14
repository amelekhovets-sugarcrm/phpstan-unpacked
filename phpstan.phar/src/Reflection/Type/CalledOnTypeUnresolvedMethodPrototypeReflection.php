<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\ChangedTypeMethodReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionVariantWithPhpDocs;
use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\Reflection\Php\DummyParameterWithPhpDocs;
use PHPStan\Reflection\ResolvedMethodReflection;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use function array_map;
class CalledOnTypeUnresolvedMethodPrototypeReflection implements \PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection
{
    /**
     * @var \PHPStan\Reflection\ExtendedMethodReflection
     */
    private $methodReflection;
    /**
     * @var \PHPStan\Reflection\ClassReflection
     */
    private $resolvedDeclaringClass;
    /**
     * @var bool
     */
    private $resolveTemplateTypeMapToBounds;
    /**
     * @var \PHPStan\Type\Type
     */
    private $calledOnType;
    /**
     * @var \PHPStan\Reflection\ExtendedMethodReflection|null
     */
    private $transformedMethod;
    /**
     * @var \PHPStan\Reflection\Type\CalledOnTypeUnresolvedMethodPrototypeReflection|null
     */
    private $cachedDoNotResolveTemplateTypeMapToBounds;
    public function __construct(ExtendedMethodReflection $methodReflection, ClassReflection $resolvedDeclaringClass, bool $resolveTemplateTypeMapToBounds, Type $calledOnType)
    {
        $this->methodReflection = $methodReflection;
        $this->resolvedDeclaringClass = $resolvedDeclaringClass;
        $this->resolveTemplateTypeMapToBounds = $resolveTemplateTypeMapToBounds;
        $this->calledOnType = $calledOnType;
    }
    public function doNotResolveTemplateTypeMapToBounds() : \PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection
    {
        if ($this->cachedDoNotResolveTemplateTypeMapToBounds !== null) {
            return $this->cachedDoNotResolveTemplateTypeMapToBounds;
        }
        return $this->cachedDoNotResolveTemplateTypeMapToBounds = new self($this->methodReflection, $this->resolvedDeclaringClass, \false, $this->calledOnType);
    }
    public function getNakedMethod() : ExtendedMethodReflection
    {
        return $this->methodReflection;
    }
    public function getTransformedMethod() : ExtendedMethodReflection
    {
        if ($this->transformedMethod !== null) {
            return $this->transformedMethod;
        }
        $templateTypeMap = $this->resolvedDeclaringClass->getActiveTemplateTypeMap();
        $callSiteVarianceMap = $this->resolvedDeclaringClass->getCallSiteVarianceMap();
        return $this->transformedMethod = new ResolvedMethodReflection($this->transformMethodWithStaticType($this->resolvedDeclaringClass, $this->methodReflection), $this->resolveTemplateTypeMapToBounds ? $templateTypeMap->resolveToBounds() : $templateTypeMap, $callSiteVarianceMap);
    }
    public function withCalledOnType(Type $type) : \PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection
    {
        return new self($this->methodReflection, $this->resolvedDeclaringClass, $this->resolveTemplateTypeMapToBounds, $type);
    }
    private function transformMethodWithStaticType(ClassReflection $declaringClass, ExtendedMethodReflection $method) : ExtendedMethodReflection
    {
        $variants = array_map(function (ParametersAcceptorWithPhpDocs $acceptor) : ParametersAcceptorWithPhpDocs {
            return new FunctionVariantWithPhpDocs($acceptor->getTemplateTypeMap(), $acceptor->getResolvedTemplateTypeMap(), array_map(function (ParameterReflectionWithPhpDocs $parameter) : ParameterReflectionWithPhpDocs {
                return new DummyParameterWithPhpDocs($parameter->getName(), $this->transformStaticType($parameter->getType()), $parameter->isOptional(), $parameter->passedByReference(), $parameter->isVariadic(), $parameter->getDefaultValue(), $parameter->getNativeType(), $parameter->getPhpDocType(), $parameter->getOutType());
            }, $acceptor->getParameters()), $acceptor->isVariadic(), $this->transformStaticType($acceptor->getReturnType()), $this->transformStaticType($acceptor->getPhpDocReturnType()), $this->transformStaticType($acceptor->getNativeReturnType()), $acceptor->getCallSiteVarianceMap());
        }, $method->getVariants());
        return new ChangedTypeMethodReflection($declaringClass, $method, $variants);
    }
    private function transformStaticType(Type $type) : Type
    {
        return TypeTraverser::map($type, function (Type $type, callable $traverse) : Type {
            if ($type instanceof StaticType) {
                return $this->calledOnType;
            }
            return $traverse($type);
        });
    }
}
