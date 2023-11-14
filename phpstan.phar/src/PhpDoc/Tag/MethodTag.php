<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Type;
/** @api */
class MethodTag
{
    /**
     * @var \PHPStan\Type\Type
     */
    private $returnType;
    /**
     * @var bool
     */
    private $isStatic;
    /**
     * @var array<string, MethodTagParameter>
     */
    private $parameters;
    /**
     * @param array<string, MethodTagParameter> $parameters
     */
    public function __construct(Type $returnType, bool $isStatic, array $parameters)
    {
        $this->returnType = $returnType;
        $this->isStatic = $isStatic;
        $this->parameters = $parameters;
    }
    public function getReturnType() : Type
    {
        return $this->returnType;
    }
    public function isStatic() : bool
    {
        return $this->isStatic;
    }
    /**
     * @return array<string, MethodTagParameter>
     */
    public function getParameters() : array
    {
        return $this->parameters;
    }
}
