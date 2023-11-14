<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\Type;
/** @api */
class FunctionVariant implements \PHPStan\Reflection\ParametersAcceptor
{
    /**
     * @var \PHPStan\Type\Generic\TemplateTypeMap
     */
    private $templateTypeMap;
    /**
     * @var \PHPStan\Type\Generic\TemplateTypeMap|null
     */
    private $resolvedTemplateTypeMap;
    /**
     * @var array<int, ParameterReflection>
     */
    private $parameters;
    /**
     * @var bool
     */
    private $isVariadic;
    /**
     * @var \PHPStan\Type\Type
     */
    private $returnType;
    /**
     * @var \PHPStan\Type\Generic\TemplateTypeVarianceMap
     */
    private $callSiteVarianceMap;
    /**
     * @api
     * @param array<int, ParameterReflection> $parameters
     */
    public function __construct(TemplateTypeMap $templateTypeMap, ?TemplateTypeMap $resolvedTemplateTypeMap, array $parameters, bool $isVariadic, Type $returnType, ?TemplateTypeVarianceMap $callSiteVarianceMap = null)
    {
        $this->templateTypeMap = $templateTypeMap;
        $this->resolvedTemplateTypeMap = $resolvedTemplateTypeMap;
        $this->parameters = $parameters;
        $this->isVariadic = $isVariadic;
        $this->returnType = $returnType;
        $this->callSiteVarianceMap = $callSiteVarianceMap ?? TemplateTypeVarianceMap::createEmpty();
    }
    public function getTemplateTypeMap() : TemplateTypeMap
    {
        return $this->templateTypeMap;
    }
    public function getResolvedTemplateTypeMap() : TemplateTypeMap
    {
        return $this->resolvedTemplateTypeMap ?? TemplateTypeMap::createEmpty();
    }
    public function getCallSiteVarianceMap() : TemplateTypeVarianceMap
    {
        return $this->callSiteVarianceMap;
    }
    /**
     * @return array<int, ParameterReflection>
     */
    public function getParameters() : array
    {
        return $this->parameters;
    }
    public function isVariadic() : bool
    {
        return $this->isVariadic;
    }
    public function getReturnType() : Type
    {
        return $this->returnType;
    }
}
