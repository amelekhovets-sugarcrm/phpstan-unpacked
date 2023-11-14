<?php

declare (strict_types=1);
namespace PHPStan\Type;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\Type\UnionTypeUnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnionTypeUnresolvedPropertyPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateUnionType;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use function array_map;
use function array_unique;
use function array_values;
use function count;
use function implode;
use function md5;
use function sprintf;
use function strpos;
/** @api */
class UnionType implements \PHPStan\Type\CompoundType
{
    /**
     * @var Type[]
     */
    private $types;
    /**
     * @var bool
     */
    private $normalized = \false;
    use NonGeneralizableTypeTrait;
    /**
     * @var bool
     */
    private $sortedTypes = \false;
    /** @var array<int, string> */
    private $cachedDescriptions = [];
    /**
     * @api
     * @param Type[] $types
     */
    public function __construct(array $types, bool $normalized = \false)
    {
        $this->types = $types;
        $this->normalized = $normalized;
        $throwException = static function () use($types) : void {
            throw new ShouldNotHappenException(sprintf('Cannot create %s with: %s', self::class, implode(', ', array_map(static function (\PHPStan\Type\Type $type) : string {
                return $type->describe(\PHPStan\Type\VerbosityLevel::value());
            }, $types))));
        };
        if (count($types) < 2) {
            $throwException();
        }
        foreach ($types as $type) {
            if (!$type instanceof \PHPStan\Type\UnionType) {
                continue;
            }
            if ($type instanceof TemplateType) {
                continue;
            }
            $throwException();
        }
    }
    /**
     * @return Type[]
     */
    public function getTypes() : array
    {
        return $this->types;
    }
    public function isNormalized() : bool
    {
        return $this->normalized;
    }
    /**
     * @return Type[]
     */
    protected function getSortedTypes() : array
    {
        if ($this->sortedTypes) {
            return $this->types;
        }
        $this->types = \PHPStan\Type\UnionTypeHelper::sortTypes($this->types);
        $this->sortedTypes = \true;
        return $this->types;
    }
    /**
     * @return string[]
     */
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
        return array_values(array_unique($this->pickFromTypes(static function (\PHPStan\Type\Type $type) {
            return $type->getObjectClassNames();
        })));
    }
    public function getObjectClassReflections() : array
    {
        return $this->pickFromTypes(static function (\PHPStan\Type\Type $type) {
            return $type->getObjectClassReflections();
        });
    }
    public function getArrays() : array
    {
        return $this->pickFromTypes(static function (\PHPStan\Type\Type $type) {
            return $type->getArrays();
        });
    }
    public function getConstantArrays() : array
    {
        return $this->pickFromTypes(static function (\PHPStan\Type\Type $type) {
            return $type->getConstantArrays();
        });
    }
    public function getConstantStrings() : array
    {
        return $this->pickFromTypes(static function (\PHPStan\Type\Type $type) {
            return $type->getConstantStrings();
        });
    }
    public function accepts(\PHPStan\Type\Type $type, bool $strictTypes) : TrinaryLogic
    {
        return $this->acceptsWithReason($type, $strictTypes)->result;
    }
    public function acceptsWithReason(\PHPStan\Type\Type $type, bool $strictTypes) : \PHPStan\Type\AcceptsResult
    {
        if ($type->equals(new \PHPStan\Type\ObjectType(DateTimeInterface::class)) && $this->accepts(new \PHPStan\Type\UnionType([new \PHPStan\Type\ObjectType(DateTime::class), new \PHPStan\Type\ObjectType(DateTimeImmutable::class)]), $strictTypes)->yes()) {
            return \PHPStan\Type\AcceptsResult::createYes();
        }
        if ($type instanceof \PHPStan\Type\CompoundType && !$type instanceof \PHPStan\Type\CallableType && !$type instanceof TemplateType && !$type instanceof \PHPStan\Type\IntersectionType) {
            return $type->isAcceptedWithReasonBy($this, $strictTypes);
        }
        $result = \PHPStan\Type\AcceptsResult::createNo();
        foreach ($this->getSortedTypes() as $i => $innerType) {
            $result = $result->or($innerType->acceptsWithReason($type, $strictTypes)->decorateReasons(static function (string $reason) use($i) {
                return sprintf('Type #%d from the union: %s', $i + 1, $reason);
            }));
        }
        if ($result->yes()) {
            return $result;
        }
        if ($type instanceof TemplateUnionType) {
            return $result->or($type->isAcceptedWithReasonBy($this, $strictTypes));
        }
        if ($type->isEnum()->yes() && !$this->isEnum()->no()) {
            $enumCasesUnion = \PHPStan\Type\TypeCombinator::union(...$type->getEnumCases());
            if (!$type->equals($enumCasesUnion)) {
                return $this->acceptsWithReason($enumCasesUnion, $strictTypes);
            }
        }
        return $result;
    }
    public function isSuperTypeOf(\PHPStan\Type\Type $otherType) : TrinaryLogic
    {
        if ($otherType instanceof self && !$otherType instanceof TemplateUnionType || $otherType instanceof \PHPStan\Type\IterableType || $otherType instanceof \PHPStan\Type\NeverType || $otherType instanceof \PHPStan\Type\ConditionalType || $otherType instanceof \PHPStan\Type\ConditionalTypeForParameter || $otherType instanceof \PHPStan\Type\IntegerRangeType) {
            return $otherType->isSubTypeOf($this);
        }
        $result = TrinaryLogic::createNo()->lazyOr($this->getTypes(), static function (\PHPStan\Type\Type $innerType) use($otherType) {
            return $innerType->isSuperTypeOf($otherType);
        });
        if ($result->yes()) {
            return $result;
        }
        if ($otherType instanceof TemplateUnionType) {
            return $result->or($otherType->isSubTypeOf($this));
        }
        return $result;
    }
    public function isSubTypeOf(\PHPStan\Type\Type $otherType) : TrinaryLogic
    {
        return TrinaryLogic::lazyExtremeIdentity($this->getTypes(), static function (\PHPStan\Type\Type $innerType) use($otherType) {
            return $otherType->isSuperTypeOf($innerType);
        });
    }
    public function isAcceptedBy(\PHPStan\Type\Type $acceptingType, bool $strictTypes) : TrinaryLogic
    {
        return $this->isAcceptedWithReasonBy($acceptingType, $strictTypes)->result;
    }
    public function isAcceptedWithReasonBy(\PHPStan\Type\Type $acceptingType, bool $strictTypes) : \PHPStan\Type\AcceptsResult
    {
        return \PHPStan\Type\AcceptsResult::extremeIdentity(...array_map(static function (\PHPStan\Type\Type $innerType) use($acceptingType, $strictTypes) {
            return $acceptingType->acceptsWithReason($innerType, $strictTypes);
        }, $this->types));
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
        if (isset($this->cachedDescriptions[$level->getLevelValue()])) {
            return $this->cachedDescriptions[$level->getLevelValue()];
        }
        $joinTypes = static function (array $types) use($level) : string {
            $typeNames = [];
            foreach ($types as $i => $type) {
                if ($type instanceof \PHPStan\Type\ClosureType || $type instanceof \PHPStan\Type\CallableType || $type instanceof TemplateUnionType) {
                    $typeNames[] = sprintf('(%s)', $type->describe($level));
                } elseif ($type instanceof TemplateType) {
                    $isLast = $i >= count($types) - 1;
                    $bound = $type->getBound();
                    if (!$isLast && ($level->isTypeOnly() || $level->isValue()) && !($bound instanceof \PHPStan\Type\MixedType && $bound->getSubtractedType() === null && !$bound instanceof TemplateMixedType)) {
                        $typeNames[] = sprintf('(%s)', $type->describe($level));
                    } else {
                        $typeNames[] = $type->describe($level);
                    }
                } elseif ($type instanceof \PHPStan\Type\IntersectionType) {
                    $intersectionDescription = $type->describe($level);
                    if (strpos($intersectionDescription, '&') !== \false) {
                        $typeNames[] = sprintf('(%s)', $type->describe($level));
                    } else {
                        $typeNames[] = $intersectionDescription;
                    }
                } else {
                    $typeNames[] = $type->describe($level);
                }
            }
            return implode('|', $typeNames);
        };
        return $this->cachedDescriptions[$level->getLevelValue()] = $level->handle(function () use($joinTypes) : string {
            $types = \PHPStan\Type\TypeCombinator::union(...array_map(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
                if ($type->isConstantValue()->yes() && $type->isTrue()->or($type->isFalse())->no()) {
                    return $type->generalize(\PHPStan\Type\GeneralizePrecision::lessSpecific());
                }
                return $type;
            }, $this->getSortedTypes()));
            if ($types instanceof \PHPStan\Type\UnionType) {
                return $joinTypes($types->getSortedTypes());
            }
            return $joinTypes([$types]);
        }, function () use($joinTypes) : string {
            return $joinTypes($this->getSortedTypes());
        });
    }
    /**
     * @param callable(Type $type): TrinaryLogic $canCallback
     * @param callable(Type $type): TrinaryLogic $hasCallback
     */
    private function hasInternal(callable $canCallback, callable $hasCallback) : TrinaryLogic
    {
        return TrinaryLogic::lazyExtremeIdentity($this->types, static function (\PHPStan\Type\Type $type) use($canCallback, $hasCallback) : TrinaryLogic {
            if ($canCallback($type)->no()) {
                return TrinaryLogic::createNo();
            }
            return $hasCallback($type);
        });
    }
    /**
     * @template TObject of object
     * @param callable(Type $type): TrinaryLogic $hasCallback
     * @param callable(Type $type): TObject $getCallback
     * @return TObject
     */
    private function getInternal(callable $hasCallback, callable $getCallback) : object
    {
        /** @var TrinaryLogic|null $result */
        $result = null;
        /** @var TObject|null $object */
        $object = null;
        foreach ($this->types as $type) {
            $has = $hasCallback($type);
            if (!$has->yes()) {
                continue;
            }
            if ($result !== null && $result->compareTo($has) !== $has) {
                continue;
            }
            $get = $getCallback($type);
            $result = $has;
            $object = $get;
        }
        if ($object === null) {
            throw new ShouldNotHappenException();
        }
        return $object;
    }
    public function getTemplateType(string $ancestorClassName, string $templateTypeName) : \PHPStan\Type\Type
    {
        return $this->unionTypes(static function (\PHPStan\Type\Type $type) use($ancestorClassName, $templateTypeName) : \PHPStan\Type\Type {
            return $type->getTemplateType($ancestorClassName, $templateTypeName);
        });
    }
    public function isObject() : TrinaryLogic
    {
        return $this->unionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isObject();
        });
    }
    public function isEnum() : TrinaryLogic
    {
        return $this->unionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isEnum();
        });
    }
    public function canAccessProperties() : TrinaryLogic
    {
        return $this->unionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->canAccessProperties();
        });
    }
    public function hasProperty(string $propertyName) : TrinaryLogic
    {
        return $this->unionResults(static function (\PHPStan\Type\Type $type) use($propertyName) : TrinaryLogic {
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
        return new UnionTypeUnresolvedPropertyPrototypeReflection($propertyName, $propertyPrototypes);
    }
    public function canCallMethods() : TrinaryLogic
    {
        return $this->unionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->canCallMethods();
        });
    }
    public function hasMethod(string $methodName) : TrinaryLogic
    {
        return $this->unionResults(static function (\PHPStan\Type\Type $type) use($methodName) : TrinaryLogic {
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
        return new UnionTypeUnresolvedMethodPrototypeReflection($methodName, $methodPrototypes);
    }
    public function canAccessConstants() : TrinaryLogic
    {
        return $this->unionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->canAccessConstants();
        });
    }
    public function hasConstant(string $constantName) : TrinaryLogic
    {
        return $this->hasInternal(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->canAccessConstants();
        }, static function (\PHPStan\Type\Type $type) use($constantName) : TrinaryLogic {
            return $type->hasConstant($constantName);
        });
    }
    public function getConstant(string $constantName) : ConstantReflection
    {
        return $this->getInternal(static function (\PHPStan\Type\Type $type) use($constantName) : TrinaryLogic {
            return $type->hasConstant($constantName);
        }, static function (\PHPStan\Type\Type $type) use($constantName) : ConstantReflection {
            return $type->getConstant($constantName);
        });
    }
    public function isIterable() : TrinaryLogic
    {
        return $this->unionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isIterable();
        });
    }
    public function isIterableAtLeastOnce() : TrinaryLogic
    {
        return $this->unionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isIterableAtLeastOnce();
        });
    }
    public function getArraySize() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getArraySize();
        });
    }
    public function getIterableKeyType() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getIterableKeyType();
        });
    }
    public function getFirstIterableKeyType() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getFirstIterableKeyType();
        });
    }
    public function getLastIterableKeyType() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getLastIterableKeyType();
        });
    }
    public function getIterableValueType() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getIterableValueType();
        });
    }
    public function getFirstIterableValueType() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getFirstIterableValueType();
        });
    }
    public function getLastIterableValueType() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getLastIterableValueType();
        });
    }
    public function isArray() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isArray();
        });
    }
    public function isConstantArray() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isConstantArray();
        });
    }
    public function isOversizedArray() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isOversizedArray();
        });
    }
    public function isList() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isList();
        });
    }
    public function isString() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isString();
        });
    }
    public function isNumericString() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isNumericString();
        });
    }
    public function isNonEmptyString() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isNonEmptyString();
        });
    }
    public function isNonFalsyString() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isNonFalsyString();
        });
    }
    public function isLiteralString() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isLiteralString();
        });
    }
    public function isClassStringType() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isClassStringType();
        });
    }
    public function getClassStringObjectType() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getClassStringObjectType();
        });
    }
    public function getObjectTypeOrClassStringObjectType() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getObjectTypeOrClassStringObjectType();
        });
    }
    public function isVoid() : TrinaryLogic
    {
        return $this->unionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isVoid();
        });
    }
    public function isScalar() : TrinaryLogic
    {
        return $this->unionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isScalar();
        });
    }
    public function looseCompare(\PHPStan\Type\Type $type, PhpVersion $phpVersion) : \PHPStan\Type\BooleanType
    {
        return new \PHPStan\Type\BooleanType();
    }
    public function isOffsetAccessible() : TrinaryLogic
    {
        return $this->unionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isOffsetAccessible();
        });
    }
    public function hasOffsetValueType(\PHPStan\Type\Type $offsetType) : TrinaryLogic
    {
        return $this->unionResults(static function (\PHPStan\Type\Type $type) use($offsetType) : TrinaryLogic {
            return $type->hasOffsetValueType($offsetType);
        });
    }
    public function getOffsetValueType(\PHPStan\Type\Type $offsetType) : \PHPStan\Type\Type
    {
        $types = [];
        foreach ($this->types as $innerType) {
            $valueType = $innerType->getOffsetValueType($offsetType);
            if ($valueType instanceof \PHPStan\Type\ErrorType) {
                continue;
            }
            $types[] = $valueType;
        }
        if (count($types) === 0) {
            return new \PHPStan\Type\ErrorType();
        }
        return \PHPStan\Type\TypeCombinator::union(...$types);
    }
    public function setOffsetValueType(?\PHPStan\Type\Type $offsetType, \PHPStan\Type\Type $valueType, bool $unionValues = \true) : \PHPStan\Type\Type
    {
        return $this->unionTypes(static function (\PHPStan\Type\Type $type) use($offsetType, $valueType, $unionValues) : \PHPStan\Type\Type {
            return $type->setOffsetValueType($offsetType, $valueType, $unionValues);
        });
    }
    public function unsetOffset(\PHPStan\Type\Type $offsetType) : \PHPStan\Type\Type
    {
        return $this->unionTypes(static function (\PHPStan\Type\Type $type) use($offsetType) : \PHPStan\Type\Type {
            return $type->unsetOffset($offsetType);
        });
    }
    public function getKeysArray() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getKeysArray();
        });
    }
    public function getValuesArray() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getValuesArray();
        });
    }
    public function fillKeysArray(\PHPStan\Type\Type $valueType) : \PHPStan\Type\Type
    {
        return $this->unionTypes(static function (\PHPStan\Type\Type $type) use($valueType) : \PHPStan\Type\Type {
            return $type->fillKeysArray($valueType);
        });
    }
    public function flipArray() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->flipArray();
        });
    }
    public function intersectKeyArray(\PHPStan\Type\Type $otherArraysType) : \PHPStan\Type\Type
    {
        return $this->unionTypes(static function (\PHPStan\Type\Type $type) use($otherArraysType) : \PHPStan\Type\Type {
            return $type->intersectKeyArray($otherArraysType);
        });
    }
    public function popArray() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->popArray();
        });
    }
    public function searchArray(\PHPStan\Type\Type $needleType) : \PHPStan\Type\Type
    {
        return $this->unionTypes(static function (\PHPStan\Type\Type $type) use($needleType) : \PHPStan\Type\Type {
            return $type->searchArray($needleType);
        });
    }
    public function shiftArray() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->shiftArray();
        });
    }
    public function shuffleArray() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->shuffleArray();
        });
    }
    public function getEnumCases() : array
    {
        return $this->pickFromTypes(static function (\PHPStan\Type\Type $type) {
            return $type->getEnumCases();
        });
    }
    public function isCallable() : TrinaryLogic
    {
        return $this->unionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isCallable();
        });
    }
    /**
     * @return ParametersAcceptor[]
     */
    public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope) : array
    {
        foreach ($this->types as $type) {
            if ($type->isCallable()->no()) {
                continue;
            }
            return $type->getCallableParametersAcceptors($scope);
        }
        throw new ShouldNotHappenException();
    }
    public function isCloneable() : TrinaryLogic
    {
        return $this->unionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isCloneable();
        });
    }
    public function isSmallerThan(\PHPStan\Type\Type $otherType) : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static function (\PHPStan\Type\Type $type) use($otherType) : TrinaryLogic {
            return $type->isSmallerThan($otherType);
        });
    }
    public function isSmallerThanOrEqual(\PHPStan\Type\Type $otherType) : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static function (\PHPStan\Type\Type $type) use($otherType) : TrinaryLogic {
            return $type->isSmallerThanOrEqual($otherType);
        });
    }
    public function isNull() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isNull();
        });
    }
    public function isConstantValue() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isConstantValue();
        });
    }
    public function isConstantScalarValue() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isConstantScalarValue();
        });
    }
    public function getConstantScalarTypes() : array
    {
        return $this->notBenevolentPickFromTypes(static function (\PHPStan\Type\Type $type) {
            return $type->getConstantScalarTypes();
        });
    }
    public function getConstantScalarValues() : array
    {
        return $this->notBenevolentPickFromTypes(static function (\PHPStan\Type\Type $type) {
            return $type->getConstantScalarValues();
        });
    }
    public function isTrue() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isTrue();
        });
    }
    public function isFalse() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isFalse();
        });
    }
    public function isBoolean() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isBoolean();
        });
    }
    public function isFloat() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isFloat();
        });
    }
    public function isInteger() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static function (\PHPStan\Type\Type $type) : TrinaryLogic {
            return $type->isInteger();
        });
    }
    public function getSmallerType() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getSmallerType();
        });
    }
    public function getSmallerOrEqualType() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getSmallerOrEqualType();
        });
    }
    public function getGreaterType() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getGreaterType();
        });
    }
    public function getGreaterOrEqualType() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->getGreaterOrEqualType();
        });
    }
    public function isGreaterThan(\PHPStan\Type\Type $otherType) : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static function (\PHPStan\Type\Type $type) use($otherType) : TrinaryLogic {
            return $otherType->isSmallerThan($type);
        });
    }
    public function isGreaterThanOrEqual(\PHPStan\Type\Type $otherType) : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static function (\PHPStan\Type\Type $type) use($otherType) : TrinaryLogic {
            return $otherType->isSmallerThanOrEqual($type);
        });
    }
    public function toBoolean() : \PHPStan\Type\BooleanType
    {
        /** @var BooleanType $type */
        $type = $this->unionTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\BooleanType {
            return $type->toBoolean();
        });
        return $type;
    }
    public function toNumber() : \PHPStan\Type\Type
    {
        $type = $this->unionTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->toNumber();
        });
        return $type;
    }
    public function toString() : \PHPStan\Type\Type
    {
        $type = $this->unionTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->toString();
        });
        return $type;
    }
    public function toInteger() : \PHPStan\Type\Type
    {
        $type = $this->unionTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->toInteger();
        });
        return $type;
    }
    public function toFloat() : \PHPStan\Type\Type
    {
        $type = $this->unionTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->toFloat();
        });
        return $type;
    }
    public function toArray() : \PHPStan\Type\Type
    {
        $type = $this->unionTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->toArray();
        });
        return $type;
    }
    public function toArrayKey() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type->toArrayKey();
        });
    }
    public function inferTemplateTypes(\PHPStan\Type\Type $receivedType) : TemplateTypeMap
    {
        $types = TemplateTypeMap::createEmpty();
        if ($receivedType instanceof \PHPStan\Type\UnionType) {
            $myTypes = [];
            $remainingReceivedTypes = [];
            foreach ($receivedType->getTypes() as $receivedInnerType) {
                foreach ($this->types as $type) {
                    if ($type->isSuperTypeOf($receivedInnerType)->yes()) {
                        $types = $types->union($type->inferTemplateTypes($receivedInnerType));
                        continue 2;
                    }
                    $myTypes[] = $type;
                }
                $remainingReceivedTypes[] = $receivedInnerType;
            }
            if (count($remainingReceivedTypes) === 0) {
                return $types;
            }
            $receivedType = \PHPStan\Type\TypeCombinator::union(...$remainingReceivedTypes);
        } else {
            $myTypes = $this->types;
        }
        foreach ($myTypes as $type) {
            if ($type instanceof TemplateType || $type instanceof GenericClassStringType && $type->getGenericType() instanceof TemplateType) {
                continue;
            }
            $types = $types->union($type->inferTemplateTypes($receivedType));
        }
        if (!$types->isEmpty()) {
            return $types;
        }
        foreach ($myTypes as $type) {
            $types = $types->union($type->inferTemplateTypes($receivedType));
        }
        return $types;
    }
    public function inferTemplateTypesOn(\PHPStan\Type\Type $templateType) : TemplateTypeMap
    {
        $types = TemplateTypeMap::createEmpty();
        foreach ($this->types as $type) {
            $types = $types->union($templateType->inferTemplateTypes($type));
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
            return \PHPStan\Type\TypeCombinator::union(...$types);
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
            return \PHPStan\Type\TypeCombinator::union(...$types);
        }
        return $this;
    }
    public function tryRemove(\PHPStan\Type\Type $typeToRemove) : ?\PHPStan\Type\Type
    {
        return $this->unionTypes(static function (\PHPStan\Type\Type $type) use($typeToRemove) : \PHPStan\Type\Type {
            return \PHPStan\Type\TypeCombinator::remove($type, $typeToRemove);
        });
    }
    public function exponentiate(\PHPStan\Type\Type $exponent) : \PHPStan\Type\Type
    {
        return $this->unionTypes(static function (\PHPStan\Type\Type $type) use($exponent) : \PHPStan\Type\Type {
            return $type->exponentiate($exponent);
        });
    }
    public function getFiniteTypes() : array
    {
        $types = $this->notBenevolentPickFromTypes(static function (\PHPStan\Type\Type $type) {
            return $type->getFiniteTypes();
        });
        $uniquedTypes = [];
        foreach ($types as $type) {
            $uniquedTypes[md5($type->describe(\PHPStan\Type\VerbosityLevel::cache()))] = $type;
        }
        if (count($uniquedTypes) > InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT) {
            return [];
        }
        return array_values($uniquedTypes);
    }
    /**
     * @param mixed[] $properties
     */
    public static function __set_state(array $properties) : \PHPStan\Type\Type
    {
        return new self($properties['types'], $properties['normalized']);
    }
    /**
     * @param callable(Type $type): TrinaryLogic $getResult
     */
    protected function unionResults(callable $getResult) : TrinaryLogic
    {
        return TrinaryLogic::lazyExtremeIdentity($this->types, $getResult);
    }
    /**
     * @param callable(Type $type): TrinaryLogic $getResult
     */
    private function notBenevolentUnionResults(callable $getResult) : TrinaryLogic
    {
        return TrinaryLogic::lazyExtremeIdentity($this->types, $getResult);
    }
    /**
     * @param callable(Type $type): Type $getType
     */
    protected function unionTypes(callable $getType) : \PHPStan\Type\Type
    {
        return \PHPStan\Type\TypeCombinator::union(...array_map($getType, $this->types));
    }
    /**
     * @template T of Type
     * @param callable(Type $type): list<T> $getTypes
     * @return list<T>
     *
     * @deprecated Use pickFromTypes() instead.
     */
    protected function pickTypes(callable $getTypes) : array
    {
        return $this->pickFromTypes($getTypes);
    }
    /**
     * @template T
     * @param callable(Type $type): list<T> $getValues
     * @return list<T>
     */
    protected function pickFromTypes(callable $getValues) : array
    {
        $values = [];
        foreach ($this->types as $type) {
            $innerValues = $getValues($type);
            if ($innerValues === []) {
                return [];
            }
            foreach ($innerValues as $innerType) {
                $values[] = $innerType;
            }
        }
        return $values;
    }
    public function toPhpDocNode() : TypeNode
    {
        return new UnionTypeNode(array_map(static function (\PHPStan\Type\Type $type) {
            return $type->toPhpDocNode();
        }, $this->getSortedTypes()));
    }
    /**
     * @template T
     * @param callable(Type $type): list<T> $getValues
     * @return list<T>
     */
    private function notBenevolentPickFromTypes(callable $getValues) : array
    {
        $values = [];
        foreach ($this->types as $type) {
            $innerValues = $getValues($type);
            if ($innerValues === []) {
                return [];
            }
            foreach ($innerValues as $innerType) {
                $values[] = $innerType;
            }
        }
        return $values;
    }
}
