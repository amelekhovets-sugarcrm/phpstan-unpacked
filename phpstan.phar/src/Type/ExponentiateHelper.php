<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use function is_float;
use function is_int;
final class ExponentiateHelper
{
    public static function exponentiate(\PHPStan\Type\Type $base, \PHPStan\Type\Type $exponent) : \PHPStan\Type\Type
    {
        if ($exponent instanceof \PHPStan\Type\UnionType) {
            $results = [];
            foreach ($exponent->getTypes() as $unionType) {
                $results[] = self::exponentiate($base, $unionType);
            }
            return \PHPStan\Type\TypeCombinator::union(...$results);
        }
        if ($exponent instanceof \PHPStan\Type\NeverType) {
            return new \PHPStan\Type\NeverType();
        }
        $allowedExponentTypes = new \PHPStan\Type\UnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\FloatType(), new \PHPStan\Type\StringType(), new \PHPStan\Type\BooleanType(), new \PHPStan\Type\NullType()]);
        if (!$allowedExponentTypes->isSuperTypeOf($exponent)->yes()) {
            return new \PHPStan\Type\ErrorType();
        }
        if ($base instanceof \PHPStan\Type\ConstantScalarType) {
            $result = self::exponentiateConstantScalar($base, $exponent);
            if ($result !== null) {
                return $result;
            }
        }
        // exponentiation of a float, stays a float
        $float = new \PHPStan\Type\FloatType();
        $isFloatBase = $float->isSuperTypeOf($base)->yes();
        $isLooseZero = (new ConstantIntegerType(0))->isSuperTypeOf($exponent->toNumber());
        if ($isLooseZero->yes()) {
            if ($isFloatBase) {
                return new ConstantFloatType(1);
            }
            return new ConstantIntegerType(1);
        }
        $isLooseOne = (new ConstantIntegerType(1))->isSuperTypeOf($exponent->toNumber());
        if ($isLooseOne->yes()) {
            $possibleResults = new \PHPStan\Type\UnionType([new \PHPStan\Type\FloatType(), new \PHPStan\Type\IntegerType()]);
            if ($possibleResults->isSuperTypeOf($base)->yes()) {
                return $base;
            }
        }
        if ($isFloatBase) {
            return new \PHPStan\Type\FloatType();
        }
        return new \PHPStan\Type\BenevolentUnionType([new \PHPStan\Type\FloatType(), new \PHPStan\Type\IntegerType()]);
    }
    private static function exponentiateConstantScalar(\PHPStan\Type\ConstantScalarType $base, \PHPStan\Type\Type $exponent) : ?\PHPStan\Type\Type
    {
        if ($exponent instanceof \PHPStan\Type\IntegerRangeType) {
            $min = null;
            $max = null;
            if ($exponent->getMin() !== null) {
                $min = $base->getValue() ** $exponent->getMin();
            }
            if ($exponent->getMax() !== null) {
                $max = $base->getValue() ** $exponent->getMax();
            }
            if (!is_float($min) && !is_float($max)) {
                return \PHPStan\Type\IntegerRangeType::fromInterval($min, $max);
            }
        }
        if ($exponent instanceof \PHPStan\Type\ConstantScalarType) {
            $result = $base->getValue() ** $exponent->getValue();
            if (is_int($result)) {
                return new ConstantIntegerType($result);
            }
            return new ConstantFloatType($result);
        }
        return null;
    }
}