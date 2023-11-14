<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\TrinaryLogic;
class StringAlwaysAcceptingObjectWithToStringType extends \PHPStan\Type\StringType
{
    public function isSuperTypeOf(\PHPStan\Type\Type $type) : TrinaryLogic
    {
        if ($type instanceof \PHPStan\Type\CompoundType) {
            return $type->isSubTypeOf($this);
        }
        $thatClassNames = $type->getObjectClassNames();
        if ($thatClassNames === []) {
            return parent::isSuperTypeOf($type);
        }
        $result = TrinaryLogic::createNo();
        $reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
        foreach ($thatClassNames as $thatClassName) {
            if (!$reflectionProvider->hasClass($thatClassName)) {
                return TrinaryLogic::createNo();
            }
            $typeClass = $reflectionProvider->getClass($thatClassName);
            $result = $result->or(TrinaryLogic::createFromBoolean($typeClass->hasNativeMethod('__toString')));
        }
        return $result;
    }
    public function acceptsWithReason(\PHPStan\Type\Type $type, bool $strictTypes) : \PHPStan\Type\AcceptsResult
    {
        $thatClassNames = $type->getObjectClassNames();
        if ($thatClassNames === []) {
            return parent::acceptsWithReason($type, $strictTypes);
        }
        $result = \PHPStan\Type\AcceptsResult::createNo();
        $reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
        foreach ($thatClassNames as $thatClassName) {
            if (!$reflectionProvider->hasClass($thatClassName)) {
                return \PHPStan\Type\AcceptsResult::createNo();
            }
            $typeClass = $reflectionProvider->getClass($thatClassName);
            $result = $result->or(\PHPStan\Type\AcceptsResult::createFromBoolean($typeClass->hasNativeMethod('__toString')));
        }
        return $result;
    }
}
