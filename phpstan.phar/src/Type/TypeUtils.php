<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\Generic\TemplateBenevolentUnionType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateUnionType;
use function array_merge;
use function array_unique;
use function array_values;
/** @api */
class TypeUtils
{
    /**
     * @return ArrayType[]
     *
     * @deprecated Use PHPStan\Type\Type::getArrays() instead and handle optional ConstantArrayType keys if necessary.
     */
    public static function getArrays(\PHPStan\Type\Type $type) : array
    {
        if ($type instanceof ConstantArrayType) {
            return $type->getAllArrays();
        }
        if ($type instanceof \PHPStan\Type\ArrayType) {
            return [$type];
        }
        if ($type instanceof \PHPStan\Type\UnionType) {
            $matchingTypes = [];
            foreach ($type->getTypes() as $innerType) {
                if (!$innerType instanceof \PHPStan\Type\ArrayType) {
                    return [];
                }
                foreach (self::getArrays($innerType) as $innerInnerType) {
                    $matchingTypes[] = $innerInnerType;
                }
            }
            return $matchingTypes;
        }
        if ($type instanceof \PHPStan\Type\IntersectionType) {
            $matchingTypes = [];
            foreach ($type->getTypes() as $innerType) {
                if (!$innerType instanceof \PHPStan\Type\ArrayType) {
                    continue;
                }
                foreach (self::getArrays($innerType) as $innerInnerType) {
                    $matchingTypes[] = $innerInnerType;
                }
            }
            return $matchingTypes;
        }
        return [];
    }
    /**
     * @return ConstantArrayType[]
     *
     * @deprecated Use PHPStan\Type\Type::getConstantArrays() instead and handle optional keys if necessary.
     */
    public static function getConstantArrays(\PHPStan\Type\Type $type) : array
    {
        if ($type instanceof ConstantArrayType) {
            return $type->getAllArrays();
        }
        if ($type instanceof \PHPStan\Type\UnionType) {
            $matchingTypes = [];
            foreach ($type->getTypes() as $innerType) {
                if (!$innerType instanceof ConstantArrayType) {
                    return [];
                }
                foreach (self::getConstantArrays($innerType) as $innerInnerType) {
                    $matchingTypes[] = $innerInnerType;
                }
            }
            return $matchingTypes;
        }
        return [];
    }
    /**
     * @return ConstantStringType[]
     *
     * @deprecated Use PHPStan\Type\Type::getConstantStrings() instead
     */
    public static function getConstantStrings(\PHPStan\Type\Type $type) : array
    {
        return self::map(ConstantStringType::class, $type, \false);
    }
    /**
     * @return ConstantIntegerType[]
     */
    public static function getConstantIntegers(\PHPStan\Type\Type $type) : array
    {
        return self::map(ConstantIntegerType::class, $type, \false);
    }
    /**
     * @deprecated Use Type::isConstantValue() or Type::generalize()
     * @return ConstantType[]
     */
    public static function getConstantTypes(\PHPStan\Type\Type $type) : array
    {
        return self::map(\PHPStan\Type\ConstantType::class, $type, \false);
    }
    /**
     * @deprecated Use Type::isConstantValue() or Type::generalize()
     * @return ConstantType[]
     */
    public static function getAnyConstantTypes(\PHPStan\Type\Type $type) : array
    {
        return self::map(\PHPStan\Type\ConstantType::class, $type, \false, \false);
    }
    /**
     * @return ArrayType[]
     *
     * @deprecated Use PHPStan\Type\Type::getArrays() instead.
     */
    public static function getAnyArrays(\PHPStan\Type\Type $type) : array
    {
        return self::map(\PHPStan\Type\ArrayType::class, $type, \true, \false);
    }
    /**
     * @deprecated Use PHPStan\Type\Type::generalize() instead.
     */
    public static function generalizeType(\PHPStan\Type\Type $type, \PHPStan\Type\GeneralizePrecision $precision) : \PHPStan\Type\Type
    {
        return $type->generalize($precision);
    }
    /**
     * @return list<string>
     *
     * @deprecated Use Type::getObjectClassNames() instead.
     */
    public static function getDirectClassNames(\PHPStan\Type\Type $type) : array
    {
        if ($type instanceof \PHPStan\Type\TypeWithClassName) {
            return [$type->getClassName()];
        }
        if ($type instanceof \PHPStan\Type\UnionType || $type instanceof \PHPStan\Type\IntersectionType) {
            $classNames = [];
            foreach ($type->getTypes() as $innerType) {
                foreach (self::getDirectClassNames($innerType) as $n) {
                    $classNames[] = $n;
                }
            }
            return array_values(array_unique($classNames));
        }
        return [];
    }
    /**
     * @return IntegerRangeType[]
     */
    public static function getIntegerRanges(\PHPStan\Type\Type $type) : array
    {
        return self::map(\PHPStan\Type\IntegerRangeType::class, $type, \false);
    }
    /**
     * @deprecated Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues()
     * @return ConstantScalarType[]
     */
    public static function getConstantScalars(\PHPStan\Type\Type $type) : array
    {
        return self::map(\PHPStan\Type\ConstantScalarType::class, $type, \false);
    }
    /**
     * @deprecated Use Type::getEnumCases()
     * @return EnumCaseObjectType[]
     */
    public static function getEnumCaseObjects(\PHPStan\Type\Type $type) : array
    {
        return self::map(EnumCaseObjectType::class, $type, \false);
    }
    /**
     * @internal
     * @return ConstantArrayType[]
     *
     * @deprecated Use PHPStan\Type\Type::getConstantArrays().
     */
    public static function getOldConstantArrays(\PHPStan\Type\Type $type) : array
    {
        return self::map(ConstantArrayType::class, $type, \false);
    }
    /**
     * @return mixed[]
     */
    private static function map(string $typeClass, \PHPStan\Type\Type $type, bool $inspectIntersections, bool $stopOnUnmatched = \true) : array
    {
        if ($type instanceof $typeClass) {
            return [$type];
        }
        if ($type instanceof \PHPStan\Type\UnionType) {
            $matchingTypes = [];
            foreach ($type->getTypes() as $innerType) {
                $matchingInner = self::map($typeClass, $innerType, $inspectIntersections, $stopOnUnmatched);
                if ($matchingInner === []) {
                    if ($stopOnUnmatched) {
                        return [];
                    }
                    continue;
                }
                foreach ($matchingInner as $innerMapped) {
                    $matchingTypes[] = $innerMapped;
                }
            }
            return $matchingTypes;
        }
        if ($inspectIntersections && $type instanceof \PHPStan\Type\IntersectionType) {
            $matchingTypes = [];
            foreach ($type->getTypes() as $innerType) {
                if (!$innerType instanceof $typeClass) {
                    if ($stopOnUnmatched) {
                        return [];
                    }
                    continue;
                }
                $matchingTypes[] = $innerType;
            }
            return $matchingTypes;
        }
        return [];
    }
    public static function toBenevolentUnion(\PHPStan\Type\Type $type) : \PHPStan\Type\Type
    {
        if ($type instanceof \PHPStan\Type\BenevolentUnionType) {
            return $type;
        }
        if ($type instanceof \PHPStan\Type\UnionType) {
            return new \PHPStan\Type\BenevolentUnionType($type->getTypes());
        }
        return $type;
    }
    /**
     * @return ($type is UnionType ? UnionType : Type)
     */
    public static function toStrictUnion(\PHPStan\Type\Type $type) : \PHPStan\Type\Type
    {
        if ($type instanceof TemplateBenevolentUnionType) {
            return new TemplateUnionType($type->getScope(), $type->getStrategy(), $type->getVariance(), $type->getName(), static::toStrictUnion($type->getBound()));
        }
        if ($type instanceof \PHPStan\Type\BenevolentUnionType) {
            return new \PHPStan\Type\UnionType($type->getTypes());
        }
        return $type;
    }
    /**
     * @return Type[]
     */
    public static function flattenTypes(\PHPStan\Type\Type $type) : array
    {
        if ($type instanceof ConstantArrayType) {
            return $type->getAllArrays();
        }
        if ($type instanceof \PHPStan\Type\UnionType) {
            $types = [];
            foreach ($type->getTypes() as $innerType) {
                if ($innerType instanceof ConstantArrayType) {
                    foreach ($innerType->getAllArrays() as $array) {
                        $types[] = $array;
                    }
                    continue;
                }
                $types[] = $innerType;
            }
            return $types;
        }
        return [$type];
    }
    public static function findThisType(\PHPStan\Type\Type $type) : ?\PHPStan\Type\ThisType
    {
        if ($type instanceof \PHPStan\Type\ThisType) {
            return $type;
        }
        if ($type instanceof \PHPStan\Type\UnionType || $type instanceof \PHPStan\Type\IntersectionType) {
            foreach ($type->getTypes() as $innerType) {
                $thisType = self::findThisType($innerType);
                if ($thisType !== null) {
                    return $thisType;
                }
            }
        }
        return null;
    }
    /**
     * @return HasPropertyType[]
     */
    public static function getHasPropertyTypes(\PHPStan\Type\Type $type) : array
    {
        if ($type instanceof HasPropertyType) {
            return [$type];
        }
        if ($type instanceof \PHPStan\Type\UnionType || $type instanceof \PHPStan\Type\IntersectionType) {
            $hasPropertyTypes = [[]];
            foreach ($type->getTypes() as $innerType) {
                $hasPropertyTypes[] = self::getHasPropertyTypes($innerType);
            }
            return array_merge(...$hasPropertyTypes);
        }
        return [];
    }
    /**
     * @return AccessoryType[]
     */
    public static function getAccessoryTypes(\PHPStan\Type\Type $type) : array
    {
        return self::map(AccessoryType::class, $type, \true, \false);
    }
    /** @deprecated Use PHPStan\Type\Type::isCallable() instead. */
    public static function containsCallable(\PHPStan\Type\Type $type) : bool
    {
        if ($type->isCallable()->yes()) {
            return \true;
        }
        if ($type instanceof \PHPStan\Type\UnionType) {
            foreach ($type->getTypes() as $innerType) {
                if ($innerType->isCallable()->yes()) {
                    return \true;
                }
            }
        }
        return \false;
    }
    public static function containsTemplateType(\PHPStan\Type\Type $type) : bool
    {
        $containsTemplateType = \false;
        \PHPStan\Type\TypeTraverser::map($type, static function (\PHPStan\Type\Type $type, callable $traverse) use(&$containsTemplateType) : \PHPStan\Type\Type {
            if ($type instanceof TemplateType) {
                $containsTemplateType = \true;
            }
            return $containsTemplateType ? $type : $traverse($type);
        });
        return $containsTemplateType;
    }
    public static function resolveLateResolvableTypes(\PHPStan\Type\Type $type, bool $resolveUnresolvableTypes = \true) : \PHPStan\Type\Type
    {
        return \PHPStan\Type\TypeTraverser::map($type, static function (\PHPStan\Type\Type $type, callable $traverse) use($resolveUnresolvableTypes) : \PHPStan\Type\Type {
            while ($type instanceof \PHPStan\Type\LateResolvableType && ($resolveUnresolvableTypes || $type->isResolvable())) {
                $type = $type->resolve();
            }
            return $traverse($type);
        });
    }
}
