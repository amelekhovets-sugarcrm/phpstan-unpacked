<?php

declare (strict_types=1);
namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use function count;
use function strtolower;
class ArrayValuesFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
    /**
     * @var \PHPStan\Php\PhpVersion
     */
    private $phpVersion;
    public function __construct(PhpVersion $phpVersion)
    {
        $this->phpVersion = $phpVersion;
    }
    public function isFunctionSupported(FunctionReflection $functionReflection) : bool
    {
        return strtolower($functionReflection->getName()) === 'array_values';
    }
    public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope) : ?Type
    {
        if (count($functionCall->getArgs()) !== 1) {
            return null;
        }
        $arrayType = $scope->getType($functionCall->getArgs()[0]->value);
        if ($arrayType->isArray()->no()) {
            return $this->phpVersion->arrayFunctionsReturnNullWithNonArray() ? new NullType() : new NeverType();
        }
        return $arrayType->getValuesArray();
    }
}