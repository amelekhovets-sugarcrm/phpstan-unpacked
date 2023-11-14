<?php

declare (strict_types=1);
namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
class ArgumentBasedFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
    /** @var int[] */
    private $functionNames = ['array_unique' => 0, 'array_change_key_case' => 0, 'array_diff_assoc' => 0, 'array_diff_key' => 0, 'array_diff_uassoc' => 0, 'array_diff_ukey' => 0, 'array_diff' => 0, 'array_udiff_assoc' => 0, 'array_udiff_uassoc' => 0, 'array_udiff' => 0, 'array_intersect_assoc' => 0, 'array_intersect_uassoc' => 0, 'array_intersect_ukey' => 0, 'array_intersect' => 0, 'array_uintersect_assoc' => 0, 'array_uintersect_uassoc' => 0, 'array_uintersect' => 0];
    public function isFunctionSupported(FunctionReflection $functionReflection) : bool
    {
        return isset($this->functionNames[$functionReflection->getName()]);
    }
    public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope) : Type
    {
        $argumentPosition = $this->functionNames[$functionReflection->getName()];
        if (!isset($functionCall->getArgs()[$argumentPosition])) {
            return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
        }
        $argument = $functionCall->getArgs()[$argumentPosition];
        $argumentType = $scope->getType($argument->value);
        $argumentKeyType = $argumentType->getIterableKeyType();
        $argumentValueType = $argumentType->getIterableValueType();
        if ($argument->unpack) {
            $argumentKeyType = $argumentKeyType->generalize(GeneralizePrecision::moreSpecific());
            $argumentValueType = $argumentValueType->getIterableValueType()->generalize(GeneralizePrecision::moreSpecific());
        }
        $array = new ArrayType($argumentKeyType, $argumentValueType);
        if ($functionReflection->getName() === 'array_unique' && $argumentType->isIterableAtLeastOnce()->yes()) {
            $array = TypeCombinator::intersect($array, new NonEmptyArrayType());
        }
        return $array;
    }
}