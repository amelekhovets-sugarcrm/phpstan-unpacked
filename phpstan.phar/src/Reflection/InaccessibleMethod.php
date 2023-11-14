<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
class InaccessibleMethod implements \PHPStan\Reflection\ParametersAcceptor
{
    /**
     * @var \PHPStan\Reflection\MethodReflection
     */
    private $methodReflection;
    public function __construct(\PHPStan\Reflection\MethodReflection $methodReflection)
    {
        $this->methodReflection = $methodReflection;
    }
    public function getMethod() : \PHPStan\Reflection\MethodReflection
    {
        return $this->methodReflection;
    }
    public function getTemplateTypeMap() : TemplateTypeMap
    {
        return TemplateTypeMap::createEmpty();
    }
    public function getResolvedTemplateTypeMap() : TemplateTypeMap
    {
        return TemplateTypeMap::createEmpty();
    }
    public function getCallSiteVarianceMap() : TemplateTypeVarianceMap
    {
        return TemplateTypeVarianceMap::createEmpty();
    }
    /**
     * @return array<int, ParameterReflection>
     */
    public function getParameters() : array
    {
        return [];
    }
    public function isVariadic() : bool
    {
        return \true;
    }
    public function getReturnType() : Type
    {
        return new MixedType();
    }
}
