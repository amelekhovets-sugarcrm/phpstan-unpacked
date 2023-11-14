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
use PHPStan\Type\Type;
use function array_map;
class CallbackUnresolvedMethodPrototypeReflection implements \PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection
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
    /** @var callable(Type): Type */
    private $transformStaticTypeCallback;
    /**
     * @var \PHPStan\Reflection\ExtendedMethodReflection|null
     */
    private $transformedMethod;
    /**
     * @var \PHPStan\Reflection\Type\CallbackUnresolvedMethodPrototypeReflection|null
     */
    private $cachedDoNotResolveTemplateTypeMapToBounds;
    /**
     * @param callable(Type): Type $transformStaticTypeCallback
     */
    public function __construct(ExtendedMethodReflection $methodReflection, ClassReflection $resolvedDeclaringClass, bool $resolveTemplateTypeMapToBounds, callable $transformStaticTypeCallback)
    {
        $this->methodReflection = $methodReflection;
        $this->resolvedDeclaringClass = $resolvedDeclaringClass;
        $this->resolveTemplateTypeMapToBounds = $resolveTemplateTypeMapToBounds;
        $this->transformStaticTypeCallback = $transformStaticTypeCallback;
    }
    public function doNotResolveTemplateTypeMapToBounds() : \PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection
    {
        if ($this->cachedDoNotResolveTemplateTypeMapToBounds !== null) {
            return $this->cachedDoNotResolveTemplateTypeMapToBounds;
        }
        return $this->cachedDoNotResolveTemplateTypeMapToBounds = new self($this->methodReflection, $this->resolvedDeclaringClass, \false, $this->transformStaticTypeCallback);
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
        return new \PHPStan\Reflection\Type\CalledOnTypeUnresolvedMethodPrototypeReflection($this->methodReflection, $this->resolvedDeclaringClass, $this->resolveTemplateTypeMapToBounds, $type);
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
        $callback = $this->transformStaticTypeCallback;
        return $callback($type);
    }
}