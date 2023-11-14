<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use function array_map;
use function count;
use function get_class;
use function is_string;
use function sprintf;
use function str_ends_with;
use function strtolower;
class TypehintHelper
{
    /**
     * @param \PHPStan\Reflection\ClassReflection|string|null $selfClass
     */
    private static function getTypeObjectFromTypehint(string $typeString, $selfClass) : \PHPStan\Type\Type
    {
        switch (strtolower($typeString)) {
            case 'int':
                return new \PHPStan\Type\IntegerType();
            case 'bool':
                return new \PHPStan\Type\BooleanType();
            case 'false':
                return new ConstantBooleanType(\false);
            case 'true':
                return new ConstantBooleanType(\true);
            case 'string':
                return new \PHPStan\Type\StringType();
            case 'float':
                return new \PHPStan\Type\FloatType();
            case 'array':
                return new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType());
            case 'iterable':
                return new \PHPStan\Type\IterableType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType());
            case 'callable':
                return new \PHPStan\Type\CallableType();
            case 'void':
                return new \PHPStan\Type\VoidType();
            case 'object':
                return new \PHPStan\Type\ObjectWithoutClassType();
            case 'mixed':
                return new \PHPStan\Type\MixedType(\true);
            case 'self':
                if ($selfClass instanceof ClassReflection) {
                    $selfClass = $selfClass->getName();
                }
                return $selfClass !== null ? new \PHPStan\Type\ObjectType($selfClass) : new \PHPStan\Type\ErrorType();
            case 'parent':
                $reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
                if (is_string($selfClass)) {
                    if ($reflectionProvider->hasClass($selfClass)) {
                        $selfClass = $reflectionProvider->getClass($selfClass);
                    } else {
                        $selfClass = null;
                    }
                }
                if ($selfClass !== null) {
                    if ($selfClass->getParentClass() !== null) {
                        return new \PHPStan\Type\ObjectType($selfClass->getParentClass()->getName());
                    }
                }
                return new \PHPStan\Type\NonexistentParentClassType();
            case 'static':
                $reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
                if (is_string($selfClass)) {
                    if ($reflectionProvider->hasClass($selfClass)) {
                        $selfClass = $reflectionProvider->getClass($selfClass);
                    } else {
                        $selfClass = null;
                    }
                }
                if ($selfClass !== null) {
                    return new \PHPStan\Type\StaticType($selfClass);
                }
                return new \PHPStan\Type\ErrorType();
            case 'null':
                return new \PHPStan\Type\NullType();
            case 'never':
                return new \PHPStan\Type\NonAcceptingNeverType();
            default:
                return new \PHPStan\Type\ObjectType($typeString);
        }
    }
    /** @api
     * @param \PHPStan\Reflection\ClassReflection|string|null $selfClass */
    public static function decideTypeFromReflection(?ReflectionType $reflectionType, ?\PHPStan\Type\Type $phpDocType = null, $selfClass = null, bool $isVariadic = \false) : \PHPStan\Type\Type
    {
        if ($reflectionType === null) {
            if ($isVariadic && $phpDocType instanceof \PHPStan\Type\ArrayType) {
                $phpDocType = $phpDocType->getItemType();
            }
            return $phpDocType ?? new \PHPStan\Type\MixedType();
        }
        if ($reflectionType instanceof ReflectionUnionType) {
            $type = \PHPStan\Type\TypeCombinator::union(...array_map(static function (ReflectionType $type) use($selfClass) : \PHPStan\Type\Type {
                return self::decideTypeFromReflection($type, null, $selfClass, \false);
            }, $reflectionType->getTypes()));
            return self::decideType($type, $phpDocType);
        }
        if ($reflectionType instanceof ReflectionIntersectionType) {
            $types = [];
            foreach ($reflectionType->getTypes() as $innerReflectionType) {
                $innerType = self::decideTypeFromReflection($innerReflectionType, null, $selfClass, \false);
                if (!$innerType->isObject()->yes()) {
                    return new \PHPStan\Type\NeverType();
                }
                $types[] = $innerType;
            }
            return self::decideType(\PHPStan\Type\TypeCombinator::intersect(...$types), $phpDocType);
        }
        if (!$reflectionType instanceof ReflectionNamedType) {
            throw new ShouldNotHappenException(sprintf('Unexpected type: %s', get_class($reflectionType)));
        }
        $reflectionTypeString = $reflectionType->getName();
        $loweredReflectionTypeString = strtolower($reflectionTypeString);
        if (str_ends_with($loweredReflectionTypeString, '\\object')) {
            $reflectionTypeString = 'object';
        } elseif (str_ends_with($loweredReflectionTypeString, '\\mixed')) {
            $reflectionTypeString = 'mixed';
        } elseif (str_ends_with($loweredReflectionTypeString, '\\true')) {
            $reflectionTypeString = 'true';
        } elseif (str_ends_with($loweredReflectionTypeString, '\\false')) {
            $reflectionTypeString = 'false';
        } elseif (str_ends_with($loweredReflectionTypeString, '\\null')) {
            $reflectionTypeString = 'null';
        } elseif (str_ends_with($loweredReflectionTypeString, '\\never')) {
            $reflectionTypeString = 'never';
        }
        $type = self::getTypeObjectFromTypehint($reflectionTypeString, $selfClass);
        if ($reflectionType->allowsNull()) {
            $type = \PHPStan\Type\TypeCombinator::addNull($type);
        } elseif ($phpDocType !== null) {
            $phpDocType = \PHPStan\Type\TypeCombinator::removeNull($phpDocType);
        }
        return self::decideType($type, $phpDocType);
    }
    public static function decideType(\PHPStan\Type\Type $type, ?\PHPStan\Type\Type $phpDocType = null) : \PHPStan\Type\Type
    {
        if ($phpDocType !== null && !$phpDocType instanceof \PHPStan\Type\ErrorType) {
            if ($phpDocType instanceof \PHPStan\Type\NeverType && $phpDocType->isExplicit()) {
                return $phpDocType;
            }
            if ($type instanceof \PHPStan\Type\MixedType && !$type->isExplicitMixed() && $phpDocType->isVoid()->yes()) {
                return $phpDocType;
            }
            if (\PHPStan\Type\TypeCombinator::removeNull($type) instanceof \PHPStan\Type\IterableType) {
                if ($phpDocType instanceof \PHPStan\Type\UnionType) {
                    $innerTypes = [];
                    foreach ($phpDocType->getTypes() as $innerType) {
                        if ($innerType instanceof \PHPStan\Type\ArrayType) {
                            $innerTypes[] = new \PHPStan\Type\IterableType($innerType->getKeyType(), $innerType->getItemType());
                        } else {
                            $innerTypes[] = $innerType;
                        }
                    }
                    $phpDocType = new \PHPStan\Type\UnionType($innerTypes);
                } elseif ($phpDocType instanceof \PHPStan\Type\ArrayType) {
                    $phpDocType = new \PHPStan\Type\IterableType($phpDocType->getKeyType(), $phpDocType->getItemType());
                }
            }
            if ((!$phpDocType instanceof \PHPStan\Type\NeverType || $type instanceof \PHPStan\Type\MixedType && !$type->isExplicitMixed()) && $type->isSuperTypeOf(TemplateTypeHelper::resolveToBounds($phpDocType))->yes()) {
                $resultType = $phpDocType;
            } else {
                $resultType = $type;
            }
            if ($type instanceof \PHPStan\Type\UnionType) {
                $addToUnionTypes = [];
                foreach ($type->getTypes() as $innerType) {
                    if (!$innerType->isSuperTypeOf($resultType)->no()) {
                        continue;
                    }
                    $addToUnionTypes[] = $innerType;
                }
                if (count($addToUnionTypes) > 0) {
                    $type = \PHPStan\Type\TypeCombinator::union($resultType, ...$addToUnionTypes);
                } else {
                    $type = $resultType;
                }
            } elseif (\PHPStan\Type\TypeCombinator::containsNull($type)) {
                $type = \PHPStan\Type\TypeCombinator::addNull($resultType);
            } else {
                $type = $resultType;
            }
        }
        return $type;
    }
}
