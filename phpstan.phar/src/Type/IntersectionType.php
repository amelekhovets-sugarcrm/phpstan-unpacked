<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\Reflection\Type\IntersectionTypeUnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\IntersectionTypeUnresolvedPropertyPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonRemoveableTypeTrait;
use function array_intersect_key;
use function array_map;
use function array_shift;
use function array_unique;
use function array_values;
use function count;
use function implode;
use function in_array;
use function ksort;
use function md5;
use function sprintf;
use function strlen;
use function substr;
/** @api */
class IntersectionType implements \PHPStan\Type\CompoundType
{
    /**
     * @var Type[]
     */
    private $types;
    use NonRemoveableTypeTrait;
    use NonGeneralizableTypeTrait;
    /**
     * @var bool
     */
    private $sortedTypes = \false;
    /**
     * @api
     * @param Type[] $types
     */
    public function __construct(array $types)
    {
        $this->types = $types;
        if (count($types) < 2) {
            throw new ShouldNotHappenException(sprintf('Cannot create %s with: %s', self::class, implode(', ', array_map(static function (\PHPStan\Type\Type $type) : string {
                return $type->describe(\PHPStan\Type\VerbosityLevel::value());
            }, $types))));
        }
    }
    /**
     * @return Type[]
     */
    public function getTypes() : array
    {
        return $this->types;
    }
    /**
     * @return Type[]
     */
    private function getSortedTypes() : array
    {
        if ($this->sortedTypes) {
            return $this->types;
        }
        $this->types = \PHPStan\Type\UnionTypeHelper::sortTypes($this->types);
        $this->sortedTypes = \true;
        return $this->types;
    }
    public function inferTemplateTypesOn(\PHPStan\Type\Type $templateType) : TemplateTypeMap
    {
        $types = TemplateTypeMap::createEmpty();
        foreach ($this->types as $type) {
            $types = $types->intersect($templateType->inferTemplateTypes($type));
        }
        return $types;
    }
    public function getReferencedClasses() : array
    {
        $classes = [];
        foreach ($this->types as $type) {
            foreach ($type->getReferencedClasses() as $className) {
                $classes[] = $className;
            }
        }
        return $classes;
    }
    public function getObjectClassNames() : array
    {
        $objectClassNames = [];
        foreach ($this->types as $type) {
            $innerObjectClassNames = $type->getObjectClassNames();
            foreach ($innerObjectClassNames as $innerObjectClassName) {
                $objectClassNames[] = $innerObjectClassName;
            }
        }
        return array_values(array_unique($objectClassNames));
    }
    public function getObjectClassReflections() : array
    {
        $reflections = [];
        foreach ($this->types as $type) {
            foreach ($type->getObjectClassReflections() as $reflection) {
                $reflections[] = $reflection;
            }
        }
        return $reflections;
    }
    public function getArrays() : array
    {
        $arrays = [];
        foreach ($this->types as $type) {
            foreach ($type->getArrays() as $array) {
                $arrays[] = $array;
            }
        }
        return $arrays;
    }
    public function getConstantArrays() : array
    {
        $constantArrays = [];
        foreach ($this->types as $type) {
            foreach ($type->getConstantArrays() as $constantArray) {
                $constantArrays[] = $constantArray;
            }
        }
        return $constantArrays;
    }
    public function getConstantStrings() : array
    {
        $strings = [];
        foreach ($this->types as $type) {
            foreach ($type->getConstantStrings() as $string) {
                $strings[] = $string;
            }
        }
        return $strings;
    }
    public function accepts(\PHPStan\Type\Type $type, bool $strictTypes) : TrinaryLogic
    {
        return $this->acceptsWithReason($type, $strictTypes)->result;
    }
    public function acceptsWithReason(\PHPStan\Type\Type $otherType, bool $strictTypes) : \PHPStan\Type\AcceptsResult
    {
        $result = \PHPStan\Type\AcceptsResult::createYes();
        foreach ($this->types as $type) {
            $result = $result->and($type->acceptsWithReason($otherType, $strictTypes));
        }
        return $result;
    }
    public function isSuperTypeOf(\PHPStan\Type\Type $otherType) : TrinaryLogic
    {
        if ($otherType instanceof \PHPStan\Type\IntersectionType && $this->equals($otherType)) {
            return TrinaryLogic::createYes();
        }
        if ($otherType instanceof \PHPStan\Type\NeverType) {
            return TrinaryLogic::createYes();
        }
        return TrinaryLogic::createYes()->lazyAnd($this->getTypes(), static function (\PHPStan\Type\Type $innerType) use($otherType) {
            return $innerType->isSuperTypeOf($otherType);
        });
    }
    public function isSubTypeOf(\PHPStan\Type\Type $otherType) : TrinaryLogic
    {
        if (($otherType instanceof self || $otherType instanceof \PHPStan\Type\UnionType) && !$otherType instanceof TemplateType) {
            return $otherType->isSuperTypeOf($this);
        }
        $result = TrinaryLogic::lazyMaxMin($this->getTypes(), static function (\PHPStan\Type\Type $innerType) use($otherType) {
            return $otherType->isSuperTypeOf($innerType);
        });
        if ($this->isOversizedArray()->yes()) {
            if (!$result->no()) {
                return TrinaryLogic::createYes();
            }
        }
        return $result;
    }
    public function isAcceptedBy(\PHPStan\Type\Type $acceptingType, bool $strictTypes) : TrinaryLogic
    {
        return $this->isAcceptedWithReasonBy($acceptingType, $strictTypes)->result;
    }
    public function isAcceptedWithReasonBy(\PHPStan\Type\Type $acceptingType, bool $strictTypes) : \PHPStan\Type\AcceptsResult
    {
        $result = \PHPStan\Type\AcceptsResult::maxMin(...array_map(static function (\PHPStan\Type\Type $innerType) use($acceptingType, $strictTypes) {
            return $acceptingType->acceptsWithReason($innerType, $strictTypes);
        }, $this->types));
        if ($this->isOversizedArray()->yes()) {
            if (!$result->no()) {
                return \PHPStan\Type\AcceptsResult::createYes();
            }
        }
        return $result;
    }
    public function equals(\PHPStan\Type\Type $type) : bool
    {
        if (!$type instanceof static) {
            return \false;
        }
        if (count($this->types) !== count($type->types)) {
            return \false;
        }
        $otherTypes = $type->types;
        foreach ($this->types as $innerType) {
            $match = \false;
            foreach ($otherTypes as $i => $otherType) {
                if (!$innerType->equals($otherType)) {
                    continue;
                }
                $match = \true;
                unset($otherTypes[$i]);
                break;
            }
            if (!$match) {
                return \false;
            }
        }
        return count($otherTypes) === 0;
    }
    public function describe(\PHPStan\Type\VerbosityLevel $level) : string
    {
        return $level->handle(function () use($level) : string {
            $typeNames = [];
            foreach ($this->getSortedTypes() as $type) {
                if ($type instanceof AccessoryType) {
                    continue;
                }
                $typeNames[] = $type->generalize(\PHPStan\Type\GeneralizePrecision::lessSpecific())->describe($level);
            }
            return implode('&', $typeNames);
        }, function () use($level) : string {
            return $this->describeItself($level, \true);
        }, function () use($level) : string {
            return $this->describeItself($level, \false);
        });
    }
    private function describeItself(\PHPStan\Type\VerbosityLevel $level, bool $skipAccessoryTypes) : string
    {
        $baseTypes = [];
        $typesToDescribe = [];
        $skipTypeNames = [];
        $nonEmptyStr = \false;
        $nonFalsyStr = \false;
        foreach ($this->getSortedTypes() as $i => $type) {
            if ($type instanceof AccessoryNonEmptyStringType || $type instanceof AccessoryLiteralStringType || $type instanceof AccessoryNumericStringType || $type instanceof AccessoryNonFalsyStringType) {
                if ($type instanceof AccessoryNonFalsyStringType) {
                    $nonFalsyStr = \true;
                }
                if ($type instanceof AccessoryNonEmptyStringType) {
                    $nonEmptyStr = \true;
                }
                if ($nonEmptyStr && $nonFalsyStr) {
                    // prevent redundant 'non-empty-string&non-falsy-string'
                    foreach ($typesToDescribe as $key => $typeToDescribe) {
                        if (!$typeToDescribe instanceof AccessoryNonEmptyStringType) {
                            continue;
                        }
                        unset($typesToDescribe[$key]);
                    }
                }
                $typesToDescribe[$i] = $type;
                $skipTypeNames[] = 'string';
                continue;
            }
            if ($type instanceof NonEmptyArrayType || $type instanceof AccessoryArrayListType) {
                $typesToDescribe[$i] = $type;
                $skipTypeNames[] = 'array';
                continue;
            }
            if ($type instanceof \PHPStan\Type\CallableType && $type->isCommonCallable()) {
                $typesToDescribe[$i] = $type;
                $skipTypeNames[] = 'object';
                $skipTypeNames[] = 'string';
                continue;
            }
            if (!$type instanceof AccessoryType) {
                $baseTypes[$i] = $type;
                continue;
            }
            if ($skipAccessoryTypes) {
                continue;
            }
            $typesToDescribe[$i] = $type;
        }
        $describedTypes = [];
        foreach ($baseTypes as $i => $type) {
            $typeDescription = $type->describe($level);
            if (in_array($typeDescription, ['object', 'string'], \true) && in_array($typeDescription, $skipTypeNames, \true)) {
                foreach ($typesToDescribe as $j => $typeToDescribe) {
                    if ($typeToDescribe instanceof \PHPStan\Type\CallableType && $typeToDescribe->isCommonCallable()) {
                        $describedTypes[$i] = 'callable-' . $typeDescription;
                        unset($typesToDescribe[$j]);
                        continue 2;
                    }
                }
            }
            if (substr($typeDescription, 0, strlen('array<')) === 'array<' && in_array('array', $skipTypeNames, \true)) {
                $nonEmpty = \false;
                $typeName = 'array';
                foreach ($typesToDescribe as $j => $typeToDescribe) {
                    if ($typeToDescribe instanceof AccessoryArrayListType && substr($typeDescription, 0, strlen('array<int<0, max>, ')) === 'array<int<0, max>, ') {
                        $typeName = 'list';
                        $typeDescription = 'array<' . substr($typeDescription, strlen('array<int<0, max>, '));
                    } elseif ($typeToDescribe instanceof NonEmptyArrayType) {
                        $nonEmpty = \true;
                    } else {
                        continue;
                    }
                    unset($typesToDescribe[$j]);
                }
                if ($nonEmpty) {
                    $typeName = 'non-empty-' . $typeName;
                }
                $describedTypes[$i] = $typeName . '<' . substr($typeDescription, strlen('array<'));
                continue;
            }
            if (in_array($typeDescription, $skipTypeNames, \true)) {
                continue;
            }
            $describedTypes[$i] = $type->describe($level);
        }
        foreach ($typesToDescribe as $i => $typeToDescribe) {
            $describedTypes[$i] = $typeToDescribe->describe($level);
        }
        ksort($describedTypes);
        return implode('&', $describedTypes);
    }
    public function getTemplateType(string $ancestorClassName, string $templateTypeName) : \PHPStan\Type\Type
    {
        return $this->intersectTypes(static function (\PHPStan\Type\Type $type) use($ancestorClassName, $templateTypeName) : \PHPStan\Type\Type {
            return $type->getTemplateType($ancestorClassName, $templateTypeName);
        });
    }
    public function isObject() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isObject();
        });
    }
    public function isEnum() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isEnum();
        });
    }
    public function canAccessProperties() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->canAccessProperties();
        });
    }
    public function hasProperty(string $propertyName) : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) use($propertyName) : TrinaryLogic {
            return $type->hasProperty($propertyName);
        });
    }
    public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope) : PropertyReflection
    {
        return $this->getUnresolvedPropertyPrototype($propertyName, $scope)->getTransformedProperty();
    }
    public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope) : UnresolvedPropertyPrototypeReflection
    {
        $propertyPrototypes = [];
        foreach ($this->types as $type) {
            if (!$type->hasProperty($propertyName)->yes()) {
                continue;
            }
            $propertyPrototypes[] = $type->getUnresolvedPropertyPrototype($propertyName, $scope)->withFechedOnType($this);
        }
        $propertiesCount = count($propertyPrototypes);
        if ($propertiesCount === 0) {
            throw new ShouldNotHappenException();
        }
        if ($propertiesCount === 1) {
            return $propertyPrototypes[0];
        }
        return new IntersectionTypeUnresolvedPropertyPrototypeReflection($propertyName, $propertyPrototypes);
    }
    public function canCallMethods() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->canCallMethods();
        });
    }
    public function hasMethod(string $methodName) : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) use($methodName) : TrinaryLogic {
            return $type->hasMethod($methodName);
        });
    }
    public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope) : ExtendedMethodReflection
    {
        return $this->getUnresolvedMethodPrototype($methodName, $scope)->getTransformedMethod();
    }
    public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope) : UnresolvedMethodPrototypeReflection
    {
        $methodPrototypes = [];
        foreach ($this->types as $type) {
            if (!$type->hasMethod($methodName)->yes()) {
                continue;
            }
            $methodPrototypes[] = $type->getUnresolvedMethodPrototype($methodName, $scope)->withCalledOnType($this);
        }
        $methodsCount = count($methodPrototypes);
        if ($methodsCount === 0) {
            throw new ShouldNotHappenException();
        }
        if ($methodsCount === 1) {
            return $methodPrototypes[0];
        }
        return new IntersectionTypeUnresolvedMethodPrototypeReflection($methodName, $methodPrototypes);
    }
    public function canAccessConstants() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->canAccessConstants();
        });
    }
    public function hasConstant(string $constantName) : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) use($constantName) : TrinaryLogic {
            return $type->hasConstant($constantName);
        });
    }
    public function getConstant(string $constantName) : ConstantReflection
    {
        foreach ($this->types as $type) {
            if ($type->hasConstant($constantName)->yes()) {
                return $type->getConstant($constantName);
            }
        }
        throw new ShouldNotHappenException();
    }
    public function isIterable() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isIterable();
        });
    }
    public function isIterableAtLeastOnce() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isIterableAtLeastOnce();
        });
    }
    public function getArraySize() : \PHPStan\Type\Type
    {
        return $this->intersectTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getArraySize();
        });
    }
    public function getIterableKeyType() : \PHPStan\Type\Type
    {
        return $this->intersectTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getIterableKeyType();
        });
    }
    public function getFirstIterableKeyType() : \PHPStan\Type\Type
    {
        return $this->intersectTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getFirstIterableKeyType();
        });
    }
    public function getLastIterableKeyType() : \PHPStan\Type\Type
    {
        return $this->intersectTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getLastIterableKeyType();
        });
    }
    public function getIterableValueType() : \PHPStan\Type\Type
    {
        return $this->intersectTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getIterableValueType();
        });
    }
    public function getFirstIterableValueType() : \PHPStan\Type\Type
    {
        return $this->intersectTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getFirstIterableValueType();
        });
    }
    public function getLastIterableValueType() : \PHPStan\Type\Type
    {
        return $this->intersectTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getLastIterableValueType();
        });
    }
    public function isArray() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isArray();
        });
    }
    public function isConstantArray() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isConstantArray();
        });
    }
    public function isOversizedArray() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isOversizedArray();
        });
    }
    public function isList() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isList();
        });
    }
    public function isString() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isString();
        });
    }
    public function isNumericString() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isNumericString();
        });
    }
    public function isNonEmptyString() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isNonEmptyString();
        });
    }
    public function isNonFalsyString() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isNonFalsyString();
        });
    }
    public function isLiteralString() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isLiteralString();
        });
    }
    public function isClassStringType() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isClassStringType();
        });
    }
    public function getClassStringObjectType() : \PHPStan\Type\Type
    {
        return $this->intersectTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getClassStringObjectType();
        });
    }
    public function getObjectTypeOrClassStringObjectType() : \PHPStan\Type\Type
    {
        return $this->intersectTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getObjectTypeOrClassStringObjectType();
        });
    }
    public function isVoid() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isVoid();
        });
    }
    public function isScalar() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isScalar();
        });
    }
    public function looseCompare(\PHPStan\Type\Type $type, PhpVersion $phpVersion) : \PHPStan\Type\BooleanType
    {
        return new \PHPStan\Type\BooleanType();
    }
    public function isOffsetAccessible() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isOffsetAccessible();
        });
    }
    public function hasOffsetValueType(\PHPStan\Type\Type $offsetType) : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) use($offsetType) : TrinaryLogic {
            return $type->hasOffsetValueType($offsetType);
        });
    }
    public function getOffsetValueType(\PHPStan\Type\Type $offsetType) : \PHPStan\Type\Type
    {
        $result = $this->intersectTypes(static function (\PHPStan\Type\Type $type) use($offsetType) : \PHPStan\Type\Type {
            return $type->getOffsetValueType($offsetType);
        });
        if ($this->isOversizedArray()->yes()) {
            return \PHPStan\Type\TypeUtils::toBenevolentUnion($result);
        }
        return $result;
    }
    public function setOffsetValueType(?\PHPStan\Type\Type $offsetType, \PHPStan\Type\Type $valueType, bool $unionValues = \true) : \PHPStan\Type\Type
    {
        return $this->intersectTypes(static function (\PHPStan\Type\Type $type) use($offsetType, $valueType, $unionValues) : \PHPStan\Type\Type {
            return $type->setOffsetValueType($offsetType, $valueType, $unionValues);
        });
    }
    public function unsetOffset(\PHPStan\Type\Type $offsetType) : \PHPStan\Type\Type
    {
        return $this->intersectTypes(static function (\PHPStan\Type\Type $type) use($offsetType) : \PHPStan\Type\Type {
            return $type->unsetOffset($offsetType);
        });
    }
    public function getKeysArray() : \PHPStan\Type\Type
    {
        return $this->intersectTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getKeysArray();
        });
    }
    public function getValuesArray() : \PHPStan\Type\Type
    {
        return $this->intersectTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getValuesArray();
        });
    }
    public function fillKeysArray(\PHPStan\Type\Type $valueType) : \PHPStan\Type\Type
    {
        return $this->intersectTypes(static function (\PHPStan\Type\Type $type) use($valueType) : \PHPStan\Type\Type {
            return $type->fillKeysArray($valueType);
        });
    }
    public function flipArray() : \PHPStan\Type\Type
    {
        return $this->intersectTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->flipArray();
        });
    }
    public function intersectKeyArray(\PHPStan\Type\Type $otherArraysType) : \PHPStan\Type\Type
    {
        return $this->intersectTypes(static function (\PHPStan\Type\Type $type) use($otherArraysType) : \PHPStan\Type\Type {
            return $type->intersectKeyArray($otherArraysType);
        });
    }
    public function popArray() : \PHPStan\Type\Type
    {
        return $this->intersectTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->popArray();
        });
    }
    public function searchArray(\PHPStan\Type\Type $needleType) : \PHPStan\Type\Type
    {
        return $this->intersectTypes(static function (\PHPStan\Type\Type $type) use($needleType) : \PHPStan\Type\Type {
            return $type->searchArray($needleType);
        });
    }
    public function shiftArray() : \PHPStan\Type\Type
    {
        return $this->intersectTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->shiftArray();
        });
    }
    public function shuffleArray() : \PHPStan\Type\Type
    {
        return $this->intersectTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->shuffleArray();
        });
    }
    public function getEnumCases() : array
    {
        $compare = [];
        foreach ($this->types as $type) {
            $oneType = [];
            foreach ($type->getEnumCases() as $enumCase) {
                $oneType[md5($enumCase->describe(\PHPStan\Type\VerbosityLevel::typeOnly()))] = $enumCase;
            }
            $compare[] = $oneType;
        }
        return array_values(array_intersect_key(...$compare));
    }
    public function isCallable() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isCallable();
        });
    }
    /**
     * @return ParametersAcceptor[]
     */
    public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope) : array
    {
        if ($this->isCallable()->no()) {
            throw new ShouldNotHappenException();
        }
        return [new TrivialParametersAcceptor()];
    }
    public function isCloneable() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isCloneable();
        });
    }
    public function isSmallerThan(\PHPStan\Type\Type $otherType) : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) use($otherType) : TrinaryLogic {
            return $type->isSmallerThan($otherType);
        });
    }
    public function isSmallerThanOrEqual(\PHPStan\Type\Type $otherType) : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) use($otherType) : TrinaryLogic {
            return $type->isSmallerThanOrEqual($otherType);
        });
    }
    public function isNull() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isNull();
        });
    }
    public function isConstantValue() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isConstantValue();
        });
    }
    public function isConstantScalarValue() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isConstantScalarValue();
        });
    }
    public function getConstantScalarTypes() : array
    {
        $scalarTypes = [];
        foreach ($this->types as $type) {
            foreach ($type->getConstantScalarTypes() as $scalarType) {
                $scalarTypes[] = $scalarType;
            }
        }
        return $scalarTypes;
    }
    public function getConstantScalarValues() : array
    {
        $values = [];
        foreach ($this->types as $type) {
            foreach ($type->getConstantScalarValues() as $value) {
                $values[] = $value;
            }
        }
        return $values;
    }
    public function isTrue() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isTrue();
        });
    }
    public function isFalse() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isFalse();
        });
    }
    public function isBoolean() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isBoolean();
        });
    }
    public function isFloat() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isFloat();
        });
    }
    public function isInteger() : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isInteger();
        });
    }
    public function isGreaterThan(\PHPStan\Type\Type $otherType) : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) use($otherType) : TrinaryLogic {
            return $otherType->isSmallerThan($type);
        });
    }
    public function isGreaterThanOrEqual(\PHPStan\Type\Type $otherType) : TrinaryLogic
    {
        return $this->intersectResults(static function (\PHPStan\Type\Type $type) use($otherType) : TrinaryLogic {
            return $otherType->isSmallerThanOrEqual($type);
        });
    }
    public function getSmallerType() : \PHPStan\Type\Type
    {
        return $this->intersectTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getSmallerType();
        });
    }
    public function getSmallerOrEqualType() : \PHPStan\Type\Type
    {
        return $this->intersectTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getSmallerOrEqualType();
        });
    }
    public function getGreaterType() : \PHPStan\Type\Type
    {
        return $this->intersectTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getGreaterType();
        });
    }
    public function getGreaterOrEqualType() : \PHPStan\Type\Type
    {
        return $this->intersectTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getGreaterOrEqualType();
        });
    }
    public function toBoolean() : \PHPStan\Type\BooleanType
    {
        $type = $this->intersectTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\BooleanType {
            return $type->toBoolean();
        });
        if (!$type instanceof \PHPStan\Type\BooleanType) {
            return new \PHPStan\Type\BooleanType();
        }
        return $type;
    }
    public function toNumber() : \PHPStan\Type\Type
    {
        $type = $this->intersectTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->toNumber();
        });
        return $type;
    }
    public function toString() : \PHPStan\Type\Type
    {
        $type = $this->intersectTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->toString();
        });
        return $type;
    }
    public function toInteger() : \PHPStan\Type\Type
    {
        $type = $this->intersectTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->toInteger();
        });
        return $type;
    }
    public function toFloat() : \PHPStan\Type\Type
    {
        $type = $this->intersectTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->toFloat();
        });
        return $type;
    }
    public function toArray() : \PHPStan\Type\Type
    {
        $type = $this->intersectTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->toArray();
        });
        return $type;
    }
    public function toArrayKey() : \PHPStan\Type\Type
    {
        if ($this->isNumericString()->yes()) {
            return new \PHPStan\Type\IntegerType();
        }
        if ($this->isString()->yes()) {
            return $this;
        }
        return $this->intersectTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->toArrayKey();
        });
    }
    public function inferTemplateTypes(\PHPStan\Type\Type $receivedType) : TemplateTypeMap
    {
        $types = TemplateTypeMap::createEmpty();
        foreach ($this->types as $type) {
            $types = $types->intersect($type->inferTemplateTypes($receivedType));
        }
        return $types;
    }
    public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance) : array
    {
        $references = [];
        foreach ($this->types as $type) {
            foreach ($type->getReferencedTemplateTypes($positionVariance) as $reference) {
                $references[] = $reference;
            }
        }
        return $references;
    }
    public function traverse(callable $cb) : \PHPStan\Type\Type
    {
        $types = [];
        $changed = \false;
        foreach ($this->types as $type) {
            $newType = $cb($type);
            if ($type !== $newType) {
                $changed = \true;
            }
            $types[] = $newType;
        }
        if ($changed) {
            return \PHPStan\Type\TypeCombinator::intersect(...$types);
        }
        return $this;
    }
    public function traverseSimultaneously(\PHPStan\Type\Type $right, callable $cb) : \PHPStan\Type\Type
    {
        $types = [];
        $changed = \false;
        if (!$right instanceof self) {
            return $this;
        }
        if (count($this->getTypes()) !== count($right->getTypes())) {
            return $this;
        }
        foreach ($this->getSortedTypes() as $i => $leftType) {
            $rightType = $right->getSortedTypes()[$i];
            $newType = $cb($leftType, $rightType);
            if ($leftType !== $newType) {
                $changed = \true;
            }
            $types[] = $newType;
        }
        if ($changed) {
            return \PHPStan\Type\TypeCombinator::intersect(...$types);
        }
        return $this;
    }
    public function tryRemove(\PHPStan\Type\Type $typeToRemove) : ?\PHPStan\Type\Type
    {
        return $this->intersectTypes(static function (\PHPStan\Type\Type $type) use($typeToRemove) : \PHPStan\Type\Type {
            return \PHPStan\Type\TypeCombinator::remove($type, $typeToRemove);
        });
    }
    public function exponentiate(\PHPStan\Type\Type $exponent) : \PHPStan\Type\Type
    {
        return $this->intersectTypes(static function (\PHPStan\Type\Type $type) use($exponent) : \PHPStan\Type\Type {
            return $type->exponentiate($exponent);
        });
    }
    public function getFiniteTypes() : array
    {
        $compare = [];
        foreach ($this->types as $type) {
            $oneType = [];
            foreach ($type->getFiniteTypes() as $finiteType) {
                $oneType[md5($finiteType->describe(\PHPStan\Type\VerbosityLevel::typeOnly()))] = $finiteType;
            }
            $compare[] = $oneType;
        }
        $result = array_values(array_intersect_key(...$compare));
        if (count($result) > InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT) {
            return [];
        }
        return $result;
    }
    /**
     * @param mixed[] $properties
     */
    public static function __set_state(array $properties) : \PHPStan\Type\Type
    {
        return new self($properties['types']);
    }
    /**
     * @param callable(Type $type): TrinaryLogic $getResult
     */
    private function intersectResults(callable $getResult) : TrinaryLogic
    {
        return TrinaryLogic::lazyMaxMin($this->types, $getResult);
    }
    /**
     * @param callable(Type $type): Type $getType
     */
    private function intersectTypes(callable $getType) : \PHPStan\Type\Type
    {
        $operands = array_map($getType, $this->types);
        return \PHPStan\Type\TypeCombinator::intersect(...$operands);
    }
    public function toPhpDocNode() : TypeNode
    {
        $baseTypes = [];
        $typesToDescribe = [];
        $skipTypeNames = [];
        $nonEmptyStr = \false;
        $nonFalsyStr = \false;
        foreach ($this->getSortedTypes() as $i => $type) {
            if ($type instanceof AccessoryNonEmptyStringType || $type instanceof AccessoryLiteralStringType || $type instanceof AccessoryNumericStringType || $type instanceof AccessoryNonFalsyStringType) {
                if ($type instanceof AccessoryNonFalsyStringType) {
                    $nonFalsyStr = \true;
                }
                if ($type instanceof AccessoryNonEmptyStringType) {
                    $nonEmptyStr = \true;
                }
                if ($nonEmptyStr && $nonFalsyStr) {
                    // prevent redundant 'non-empty-string&non-falsy-string'
                    foreach ($typesToDescribe as $key => $typeToDescribe) {
                        if (!$typeToDescribe instanceof AccessoryNonEmptyStringType) {
                            continue;
                        }
                        unset($typesToDescribe[$key]);
                    }
                }
                $typesToDescribe[$i] = $type;
                $skipTypeNames[] = 'string';
                continue;
            }
            if ($type instanceof NonEmptyArrayType || $type instanceof AccessoryArrayListType) {
                $typesToDescribe[$i] = $type;
                $skipTypeNames[] = 'array';
                continue;
            }
            if (!$type instanceof AccessoryType) {
                $baseTypes[$i] = $type;
                continue;
            }
            $accessoryPhpDocNode = $type->toPhpDocNode();
            if ($accessoryPhpDocNode instanceof IdentifierTypeNode && $accessoryPhpDocNode->name === '') {
                continue;
            }
            $typesToDescribe[$i] = $type;
        }
        $describedTypes = [];
        foreach ($baseTypes as $i => $type) {
            $typeNode = $type->toPhpDocNode();
            if ($typeNode instanceof GenericTypeNode && $typeNode->type->name === 'array') {
                $nonEmpty = \false;
                $typeName = 'array';
                foreach ($typesToDescribe as $j => $typeToDescribe) {
                    if ($typeToDescribe instanceof AccessoryArrayListType) {
                        $typeName = 'list';
                        if (count($typeNode->genericTypes) > 1) {
                            array_shift($typeNode->genericTypes);
                        }
                    } elseif ($typeToDescribe instanceof NonEmptyArrayType) {
                        $nonEmpty = \true;
                    } else {
                        continue;
                    }
                    unset($typesToDescribe[$j]);
                }
                if ($nonEmpty) {
                    $typeName = 'non-empty-' . $typeName;
                }
                $describedTypes[$i] = new GenericTypeNode(new IdentifierTypeNode($typeName), $typeNode->genericTypes);
                continue;
            }
            if ($typeNode instanceof IdentifierTypeNode && in_array($typeNode->name, $skipTypeNames, \true)) {
                continue;
            }
            $describedTypes[$i] = $typeNode;
        }
        foreach ($typesToDescribe as $i => $typeToDescribe) {
            $describedTypes[$i] = $typeToDescribe->toPhpDocNode();
        }
        ksort($describedTypes);
        $describedTypes = array_values($describedTypes);
        if (count($describedTypes) === 1) {
            return $describedTypes[0];
        }
        return new IntersectionTypeNode($describedTypes);
    }
}
