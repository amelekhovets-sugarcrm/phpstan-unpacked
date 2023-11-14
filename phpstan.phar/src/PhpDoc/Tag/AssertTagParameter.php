<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc\Tag;

use PhpParser\Node\Expr;
use function sprintf;
class AssertTagParameter
{
    /**
     * @var string
     */
    private $parameterName;
    /**
     * @var string|null
     */
    private $property;
    /**
     * @var string|null
     */
    private $method;
    public function __construct(string $parameterName, ?string $property, ?string $method)
    {
        $this->parameterName = $parameterName;
        $this->property = $property;
        $this->method = $method;
    }
    public function getParameterName() : string
    {
        return $this->parameterName;
    }
    public function changeParameterName(string $parameterName) : self
    {
        return new self($parameterName, $this->property, $this->method);
    }
    public function describe() : string
    {
        if ($this->property !== null) {
            return sprintf('%s->%s', $this->parameterName, $this->property);
        }
        if ($this->method !== null) {
            return sprintf('%s->%s()', $this->parameterName, $this->method);
        }
        return $this->parameterName;
    }
    public function getExpr(Expr $parameter) : Expr
    {
        if ($this->property !== null) {
            return new Expr\PropertyFetch($parameter, $this->property);
        }
        if ($this->method !== null) {
            return new Expr\MethodCall($parameter, $this->method);
        }
        return $parameter;
    }
}
