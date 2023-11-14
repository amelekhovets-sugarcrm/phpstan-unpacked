<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\MaybeCallableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonObjectTypeTrait;
use PHPStan\Type\Traits\UndecidedBooleanTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonTypeTrait;
use function array_merge;
use function count;
use function sprintf;
/** @api */
class ArrayType implements \PHPStan\Type\Type
{
    /**
     * @var \PHPStan\Type\Type
     */
    private $itemType;
    use MaybeCallableTypeTrait;
    use NonObjectTypeTrait;
    use UndecidedBooleanTypeTrait;
    use UndecidedComparisonTypeTrait;
    use NonGeneralizableTypeTrait;
    /**
     * @var \PHPStan\Type\Type
     */
    private $keyType;
    /** @api */
    public function __construct(\PHPStan\Type\Type $keyType, \PHPStan\Type\Type $itemType)
    {
        $this->itemType = $itemType;
        if ($keyType->describe(\PHPStan\Type\VerbosityLevel::value()) === '(int|string)') {
            $keyType = new \PHPStan\Type\MixedType();
        }
        $this->keyType = $keyType;
    }
    public function getKeyType() : \PHPStan\Type\Type
    {
        return $this->keyType;
    }
    public function getItemType() : \PHPStan\Type\Type
    {
        return $this->itemType;
    }
    /**
     * @return string[]
     */
    public function getReferencedClasses() : array
    {
        return array_merge($this->keyType->getReferencedClasses(), $this->getItemType()->getReferencedClasses());
    }
    public function getObjectClassNames() : array
    {
        return [];
    }
    public function getObjectClassReflections() : array
    {
        return [];
    }
    public function getArrays() : array
    {
        return [$this];
    }
    public function getConstantArrays() : array
    {
        return [];
    }
    public function accepts(\PHPStan\Type\Type $type, bool $strictTypes) : TrinaryLogic
    {
        return $this->acceptsWithReason($type, $strictTypes)->result;
    }
    public function acceptsWithReason(\PHPStan\Type\Type $type, bool $strictTypes) : \PHPStan\Type\AcceptsResult
    {
        if ($type instanceof \PHPStan\Type\CompoundType) {
            return $type->isAcceptedWithReasonBy($this, $strictTypes);
        }
        if ($type instanceof ConstantArrayType) {
            $result = \PHPStan\Type\AcceptsResult::createYes();
            $thisKeyType = $this->keyType;
            $itemType = $this->getItemType();
            foreach ($type->getKeyTypes() as $i => $keyType) {
                $valueType = $type->getValueTypes()[$i];
                $acceptsKey = $thisKeyType->acceptsWithReason($keyType, $strictTypes);
                $acceptsValue = $itemType->acceptsWithReason($valueType, $strictTypes);
                $result = $result->and($acceptsKey)->and($acceptsValue);
            }
            return $result;
        }
        if ($type instanceof \PHPStan\Type\ArrayType) {
            return $this->getItemType()->acceptsWithReason($type->getItemType(), $strictTypes)->and($this->keyType->acceptsWithReason($type->keyType, $strictTypes));
        }
        return \PHPStan\Type\AcceptsResult::createNo();
    }
    public function isSuperTypeOf(\PHPStan\Type\Type $type) : TrinaryLogic
    {
        if ($type instanceof self) {
            return $this->getItemType()->isSuperTypeOf($type->getItemType())->and($this->keyType->isSuperTypeOf($type->keyType));
        }
        if ($type instanceof \PHPStan\Type\CompoundType) {
            return $type->isSubTypeOf($this);
        }
        return TrinaryLogic::createNo();
    }
    public function equals(\PHPStan\Type\Type $type) : bool
    {
        return $type instanceof self && $type->isConstantArray()->no() && $this->getItemType()->equals($type->getIterableValueType()) && $this->keyType->equals($type->keyType);
    }
    public function describe(\PHPStan\Type\VerbosityLevel $level) : string
    {
        $isMixedKeyType = $this->keyType instanceof \PHPStan\Type\MixedType && $this->keyType->describe(\PHPStan\Type\VerbosityLevel::precise()) === 'mixed';
        $isMixedItemType = $this->itemType instanceof \PHPStan\Type\MixedType && $this->itemType->describe(\PHPStan\Type\VerbosityLevel::precise()) === 'mixed';
        $valueHandler = function () use($level, $isMixedKeyType, $isMixedItemType) : string {
            if ($isMixedKeyType || $this->keyType instanceof \PHPStan\Type\NeverType) {
                if ($isMixedItemType || $this->itemType instanceof \PHPStan\Type\NeverType) {
                    return 'array';
                }
                return sprintf('array<%s>', $this->itemType->describe($level));
            }
            return sprintf('array<%s, %s>', $this->keyType->describe($level), $this->itemType->describe($level));
        };
        return $level->handle($valueHandler, $valueHandler, function () use($level, $isMixedKeyType, $isMixedItemType) : string {
            if ($isMixedKeyType) {
                if ($isMixedItemType) {
                    return 'array';
                }
                return sprintf('array<%s>', $this->itemType->describe($level));
            }
            return sprintf('array<%s, %s>', $this->keyType->describe($level), $this->itemType->describe($level));
        });
    }
    /**
     * @deprecated
     */
    public function generalizeKeys() : self
    {
        return new self($this->keyType->generalize(\PHPStan\Type\GeneralizePrecision::lessSpecific()), $this->itemType);
    }
    public function generalizeValues() : self
    {
        return new self($this->keyType, $this->itemType->generalize(\PHPStan\Type\GeneralizePrecision::lessSpecific()));
    }
    public function getKeysArray() : \PHPStan\Type\Type
    {
        return AccessoryArrayListType::intersectWith(new self(new \PHPStan\Type\IntegerType(), $this->getIterableKeyType()));
    }
    public function getValuesArray() : \PHPStan\Type\Type
    {
        return AccessoryArrayListType::intersectWith(new self(new \PHPStan\Type\IntegerType(), $this->itemType));
    }
    public function isIterable() : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function isIterableAtLeastOnce() : TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function getArraySize() : \PHPStan\Type\Type
    {
        return \PHPStan\Type\IntegerRangeType::fromInterval(0, null);
    }
    public function getIterableKeyType() : \PHPStan\Type\Type
    {
        $keyType = $this->keyType;
        if ($keyType instanceof \PHPStan\Type\MixedType && !$keyType instanceof TemplateMixedType) {
            return new \PHPStan\Type\BenevolentUnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]);
        }
        if ($keyType instanceof \PHPStan\Type\StrictMixedType) {
            return new \PHPStan\Type\BenevolentUnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]);
        }
        return $keyType;
    }
    public function getFirstIterableKeyType() : \PHPStan\Type\Type
    {
        return $this->getIterableKeyType();
    }
    public function getLastIterableKeyType() : \PHPStan\Type\Type
    {
        return $this->getIterableKeyType();
    }
    public function getIterableValueType() : \PHPStan\Type\Type
    {
        return $this->getItemType();
    }
    public function getFirstIterableValueType() : \PHPStan\Type\Type
    {
        return $this->getItemType();
    }
    public function getLastIterableValueType() : \PHPStan\Type\Type
    {
        return $this->getItemType();
    }
    public function isArray() : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function isConstantArray() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isOversizedArray() : TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function isList() : TrinaryLogic
    {
        if (\PHPStan\Type\IntegerRangeType::fromInterval(0, null)->isSuperTypeOf($this->getKeyType())->no()) {
            return TrinaryLogic::createNo();
        }
        return TrinaryLogic::createMaybe();
    }
    public function isNull() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isConstantValue() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isConstantScalarValue() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function getConstantScalarTypes() : array
    {
        return [];
    }
    public function getConstantScalarValues() : array
    {
        return [];
    }
    public function isTrue() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isFalse() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isBoolean() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isFloat() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isInteger() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isString() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isNumericString() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isNonEmptyString() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isNonFalsyString() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isLiteralString() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isClassStringType() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function getClassStringObjectType() : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function getObjectTypeOrClassStringObjectType() : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function isVoid() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isScalar() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function looseCompare(\PHPStan\Type\Type $type, PhpVersion $phpVersion) : \PHPStan\Type\BooleanType
    {
        return new \PHPStan\Type\BooleanType();
    }
    public function isOffsetAccessible() : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function hasOffsetValueType(\PHPStan\Type\Type $offsetType) : TrinaryLogic
    {
        $offsetType = $offsetType->toArrayKey();
        if ($this->getKeyType()->isSuperTypeOf($offsetType)->no()) {
            return TrinaryLogic::createNo();
        }
        return TrinaryLogic::createMaybe();
    }
    public function getOffsetValueType(\PHPStan\Type\Type $offsetType) : \PHPStan\Type\Type
    {
        $offsetType = $offsetType->toArrayKey();
        if ($this->getKeyType()->isSuperTypeOf($offsetType)->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        $type = $this->getItemType();
        if ($type instanceof \PHPStan\Type\ErrorType) {
            return new \PHPStan\Type\MixedType();
        }
        return $type;
    }
    public function setOffsetValueType(?\PHPStan\Type\Type $offsetType, \PHPStan\Type\Type $valueType, bool $unionValues = \true) : \PHPStan\Type\Type
    {
        if ($offsetType === null) {
            $isKeyTypeInteger = $this->keyType->isInteger();
            if ($isKeyTypeInteger->no()) {
                $offsetType = new \PHPStan\Type\IntegerType();
            } elseif ($isKeyTypeInteger->yes()) {
                $offsetType = $this->keyType;
            } else {
                $integerTypes = [];
                \PHPStan\Type\TypeTraverser::map($this->keyType, static function (\PHPStan\Type\Type $type, callable $traverse) use(&$integerTypes) : \PHPStan\Type\Type {
                    if ($type instanceof \PHPStan\Type\UnionType) {
                        return $traverse($type);
                    }
                    $isInteger = $type->isInteger();
                    if ($isInteger->yes()) {
                        $integerTypes[] = $type;
                    }
                    return $type;
                });
                if (count($integerTypes) === 0) {
                    $offsetType = $this->keyType;
                } else {
                    $offsetType = \PHPStan\Type\TypeCombinator::union(...$integerTypes);
                }
            }
        } else {
            $offsetType = $offsetType->toArrayKey();
        }
        if ($offsetType instanceof ConstantStringType || $offsetType instanceof ConstantIntegerType) {
            if ($offsetType->isSuperTypeOf($this->keyType)->yes()) {
                $builder = ConstantArrayTypeBuilder::createEmpty();
                $builder->setOffsetValueType($offsetType, $valueType);
                return $builder->getArray();
            }
            return \PHPStan\Type\TypeCombinator::intersect(new self(\PHPStan\Type\TypeCombinator::union($this->keyType, $offsetType), \PHPStan\Type\TypeCombinator::union($this->itemType, $valueType)), new HasOffsetValueType($offsetType, $valueType), new NonEmptyArrayType());
        }
        return \PHPStan\Type\TypeCombinator::intersect(new self(\PHPStan\Type\TypeCombinator::union($this->keyType, $offsetType), $unionValues ? \PHPStan\Type\TypeCombinator::union($this->itemType, $valueType) : $valueType), new NonEmptyArrayType());
    }
    public function unsetOffset(\PHPStan\Type\Type $offsetType) : \PHPStan\Type\Type
    {
        $offsetType = $offsetType->toArrayKey();
        if (($offsetType instanceof ConstantIntegerType || $offsetType instanceof ConstantStringType) && !$this->keyType->isSuperTypeOf($offsetType)->no()) {
            $keyType = \PHPStan\Type\TypeCombinator::remove($this->keyType, $offsetType);
            if ($keyType instanceof \PHPStan\Type\NeverType) {
                return new ConstantArrayType([], []);
            }
            return new self($keyType, $this->itemType);
        }
        return $this;
    }
    public function fillKeysArray(\PHPStan\Type\Type $valueType) : \PHPStan\Type\Type
    {
        $itemType = $this->getItemType();
        if ((new \PHPStan\Type\IntegerType())->isSuperTypeOf($itemType)->no()) {
            $stringKeyType = $itemType->toString();
            if ($stringKeyType instanceof \PHPStan\Type\ErrorType) {
                return $stringKeyType;
            }
            return new \PHPStan\Type\ArrayType($stringKeyType, $valueType);
        }
        return new \PHPStan\Type\ArrayType($itemType, $valueType);
    }
    public function flipArray() : \PHPStan\Type\Type
    {
        return new self($this->getIterableValueType()->toArrayKey(), $this->getIterableKeyType());
    }
    public function intersectKeyArray(\PHPStan\Type\Type $otherArraysType) : \PHPStan\Type\Type
    {
        $isKeySuperType = $otherArraysType->getIterableKeyType()->isSuperTypeOf($this->getIterableKeyType());
        if ($isKeySuperType->no()) {
            return ConstantArrayTypeBuilder::createEmpty()->getArray();
        }
        if ($isKeySuperType->yes()) {
            return $otherArraysType->isIterableAtLeastOnce()->yes() ? \PHPStan\Type\TypeCombinator::intersect($this, new NonEmptyArrayType()) : $this;
        }
        return new self($otherArraysType->getIterableKeyType(), $this->getIterableValueType());
    }
    public function popArray() : \PHPStan\Type\Type
    {
        return $this;
    }
    public function searchArray(\PHPStan\Type\Type $needleType) : \PHPStan\Type\Type
    {
        return \PHPStan\Type\TypeCombinator::union($this->getIterableKeyType(), new ConstantBooleanType(\false));
    }
    public function shiftArray() : \PHPStan\Type\Type
    {
        return $this;
    }
    public function shuffleArray() : \PHPStan\Type\Type
    {
        return AccessoryArrayListType::intersectWith(new self(new \PHPStan\Type\IntegerType(), $this->itemType));
    }
    public function isCallable() : TrinaryLogic
    {
        return TrinaryLogic::createMaybe()->and($this->itemType->isString());
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
    public function toNumber() : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function toString() : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function toInteger() : \PHPStan\Type\Type
    {
        return \PHPStan\Type\TypeCombinator::union(new ConstantIntegerType(0), new ConstantIntegerType(1));
    }
    public function toFloat() : \PHPStan\Type\Type
    {
        return \PHPStan\Type\TypeCombinator::union(new ConstantFloatType(0.0), new ConstantFloatType(1.0));
    }
    public function toArray() : \PHPStan\Type\Type
    {
        return $this;
    }
    public function toArrayKey() : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    /** @deprecated Use getArraySize() instead */
    public function count() : \PHPStan\Type\Type
    {
        return $this->getArraySize();
    }
    /** @deprecated Use $offsetType->toArrayKey() instead */
    public static function castToArrayKeyType(\PHPStan\Type\Type $offsetType) : \PHPStan\Type\Type
    {
        return $offsetType->toArrayKey();
    }
    public function inferTemplateTypes(\PHPStan\Type\Type $receivedType) : TemplateTypeMap
    {
        if ($receivedType instanceof \PHPStan\Type\UnionType || $receivedType instanceof \PHPStan\Type\IntersectionType) {
            return $receivedType->inferTemplateTypesOn($this);
        }
        if ($receivedType->isArray()->yes()) {
            $keyTypeMap = $this->getKeyType()->inferTemplateTypes($receivedType->getIterableKeyType());
            $itemTypeMap = $this->getItemType()->inferTemplateTypes($receivedType->getIterableValueType());
            return $keyTypeMap->union($itemTypeMap);
        }
        return TemplateTypeMap::createEmpty();
    }
    public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance) : array
    {
        $variance = $positionVariance->compose(TemplateTypeVariance::createCovariant());
        return array_merge($this->getKeyType()->getReferencedTemplateTypes($variance), $this->getItemType()->getReferencedTemplateTypes($variance));
    }
    public function traverse(callable $cb) : \PHPStan\Type\Type
    {
        $keyType = $cb($this->keyType);
        $itemType = $cb($this->itemType);
        if ($keyType !== $this->keyType || $itemType !== $this->itemType) {
            if ($keyType instanceof \PHPStan\Type\NeverType && $itemType instanceof \PHPStan\Type\NeverType) {
                return new ConstantArrayType([], []);
            }
            return new self($keyType, $itemType);
        }
        return $this;
    }
    public function toPhpDocNode() : TypeNode
    {
        $isMixedKeyType = $this->keyType instanceof \PHPStan\Type\MixedType && $this->keyType->describe(\PHPStan\Type\VerbosityLevel::precise()) === 'mixed';
        $isMixedItemType = $this->itemType instanceof \PHPStan\Type\MixedType && $this->itemType->describe(\PHPStan\Type\VerbosityLevel::precise()) === 'mixed';
        if ($isMixedKeyType) {
            if ($isMixedItemType) {
                return new IdentifierTypeNode('array');
            }
            return new GenericTypeNode(new IdentifierTypeNode('array'), [$this->itemType->toPhpDocNode()]);
        }
        return new GenericTypeNode(new IdentifierTypeNode('array'), [$this->keyType->toPhpDocNode(), $this->itemType->toPhpDocNode()]);
    }
    public function traverseSimultaneously(\PHPStan\Type\Type $right, callable $cb) : \PHPStan\Type\Type
    {
        $keyType = $cb($this->keyType, $right->getIterableKeyType());
        $itemType = $cb($this->itemType, $right->getIterableValueType());
        if ($keyType !== $this->keyType || $itemType !== $this->itemType) {
            if ($keyType instanceof \PHPStan\Type\NeverType && $itemType instanceof \PHPStan\Type\NeverType) {
                return new ConstantArrayType([], []);
            }
            return new self($keyType, $itemType);
        }
        return $this;
    }
    public function tryRemove(\PHPStan\Type\Type $typeToRemove) : ?\PHPStan\Type\Type
    {
        if ($typeToRemove->isConstantArray()->yes() && $typeToRemove->isIterableAtLeastOnce()->no()) {
            return \PHPStan\Type\TypeCombinator::intersect($this, new NonEmptyArrayType());
        }
        if ($typeToRemove instanceof NonEmptyArrayType) {
            return new ConstantArrayType([], []);
        }
        if ($this->isConstantArray()->yes() && $typeToRemove instanceof HasOffsetType) {
            return $this->unsetOffset($typeToRemove->getOffsetType());
        }
        if ($this->isConstantArray()->yes() && $typeToRemove instanceof HasOffsetValueType) {
            return $this->unsetOffset($typeToRemove->getOffsetType());
        }
        return null;
    }
    public function exponentiate(\PHPStan\Type\Type $exponent) : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function getFiniteTypes() : array
    {
        return [];
    }
    /**
     * @param mixed[] $properties
     */
    public static function __set_state(array $properties) : \PHPStan\Type\Type
    {
        return new self($properties['keyType'], $properties['itemType']);
    }
}
