<?php

declare (strict_types=1);
namespace PHPStan\Type;

use ArrayAccess;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\Dummy\DummyConstantReflection;
use PHPStan\Reflection\Dummy\DummyMethodReflection;
use PHPStan\Reflection\Dummy\DummyPropertyReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\Reflection\Type\CallbackUnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\CallbackUnresolvedPropertyPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\OversizedArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use function sprintf;
/** @api */
class MixedType implements \PHPStan\Type\CompoundType, \PHPStan\Type\SubtractableType
{
    /**
     * @var bool
     */
    private $isExplicitMixed = \false;
    use NonGenericTypeTrait;
    use UndecidedComparisonCompoundTypeTrait;
    use NonGeneralizableTypeTrait;
    /**
     * @var \PHPStan\Type\Type|null
     */
    private $subtractedType;
    /** @api */
    public function __construct(bool $isExplicitMixed = \false, ?\PHPStan\Type\Type $subtractedType = null)
    {
        $this->isExplicitMixed = $isExplicitMixed;
        if ($subtractedType instanceof \PHPStan\Type\NeverType) {
            $subtractedType = null;
        }
        $this->subtractedType = $subtractedType;
    }
    /**
     * @return string[]
     */
    public function getReferencedClasses() : array
    {
        return [];
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
        return [];
    }
    public function getConstantArrays() : array
    {
        return [];
    }
    public function getConstantStrings() : array
    {
        return [];
    }
    public function accepts(\PHPStan\Type\Type $type, bool $strictTypes) : TrinaryLogic
    {
        return $this->acceptsWithReason($type, $strictTypes)->result;
    }
    public function acceptsWithReason(\PHPStan\Type\Type $type, bool $strictTypes) : \PHPStan\Type\AcceptsResult
    {
        return \PHPStan\Type\AcceptsResult::createYes();
    }
    public function isSuperTypeOfMixed(\PHPStan\Type\MixedType $type) : TrinaryLogic
    {
        if ($this->subtractedType === null) {
            if ($this->isExplicitMixed) {
                if ($type->isExplicitMixed) {
                    return TrinaryLogic::createYes();
                }
                return TrinaryLogic::createMaybe();
            }
            return TrinaryLogic::createYes();
        }
        if ($type->subtractedType === null) {
            return TrinaryLogic::createMaybe();
        }
        $isSuperType = $type->subtractedType->isSuperTypeOf($this->subtractedType);
        if ($isSuperType->yes()) {
            if ($this->isExplicitMixed) {
                if ($type->isExplicitMixed) {
                    return TrinaryLogic::createYes();
                }
                return TrinaryLogic::createMaybe();
            }
            return TrinaryLogic::createYes();
        }
        return TrinaryLogic::createMaybe();
    }
    public function isSuperTypeOf(\PHPStan\Type\Type $type) : TrinaryLogic
    {
        if ($this->subtractedType === null || $type instanceof \PHPStan\Type\NeverType) {
            return TrinaryLogic::createYes();
        }
        if ($type instanceof self) {
            if ($type->subtractedType === null) {
                return TrinaryLogic::createMaybe();
            }
            $isSuperType = $type->subtractedType->isSuperTypeOf($this->subtractedType);
            if ($isSuperType->yes()) {
                return TrinaryLogic::createYes();
            }
            return TrinaryLogic::createMaybe();
        }
        return $this->subtractedType->isSuperTypeOf($type)->negate();
    }
    public function setOffsetValueType(?\PHPStan\Type\Type $offsetType, \PHPStan\Type\Type $valueType, bool $unionValues = \true) : \PHPStan\Type\Type
    {
        return new self($this->isExplicitMixed);
    }
    public function unsetOffset(\PHPStan\Type\Type $offsetType) : \PHPStan\Type\Type
    {
        if ($this->subtractedType !== null) {
            return new self($this->isExplicitMixed, \PHPStan\Type\TypeCombinator::remove($this->subtractedType, new ConstantArrayType([], [])));
        }
        return $this;
    }
    public function getKeysArray() : \PHPStan\Type\Type
    {
        if ($this->isArray()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return AccessoryArrayListType::intersectWith(new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\UnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()])));
    }
    public function getValuesArray() : \PHPStan\Type\Type
    {
        if ($this->isArray()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return AccessoryArrayListType::intersectWith(new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\MixedType($this->isExplicitMixed)));
    }
    public function fillKeysArray(\PHPStan\Type\Type $valueType) : \PHPStan\Type\Type
    {
        if ($this->isArray()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return new \PHPStan\Type\ArrayType($this->getIterableValueType(), $valueType);
    }
    public function flipArray() : \PHPStan\Type\Type
    {
        if ($this->isArray()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType($this->isExplicitMixed), new \PHPStan\Type\MixedType($this->isExplicitMixed));
    }
    public function intersectKeyArray(\PHPStan\Type\Type $otherArraysType) : \PHPStan\Type\Type
    {
        if ($this->isArray()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType($this->isExplicitMixed), new \PHPStan\Type\MixedType($this->isExplicitMixed));
    }
    public function popArray() : \PHPStan\Type\Type
    {
        if ($this->isArray()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType($this->isExplicitMixed), new \PHPStan\Type\MixedType($this->isExplicitMixed));
    }
    public function searchArray(\PHPStan\Type\Type $needleType) : \PHPStan\Type\Type
    {
        if ($this->isArray()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return \PHPStan\Type\TypeCombinator::union(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType(), new ConstantBooleanType(\false));
    }
    public function shiftArray() : \PHPStan\Type\Type
    {
        if ($this->isArray()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType($this->isExplicitMixed), new \PHPStan\Type\MixedType($this->isExplicitMixed));
    }
    public function shuffleArray() : \PHPStan\Type\Type
    {
        if ($this->isArray()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return AccessoryArrayListType::intersectWith(new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\MixedType($this->isExplicitMixed)));
    }
    public function isCallable() : TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new \PHPStan\Type\CallableType())->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function getEnumCases() : array
    {
        return [];
    }
    /**
     * @return ParametersAcceptor[]
     */
    public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope) : array
    {
        return [new TrivialParametersAcceptor()];
    }
    public function equals(\PHPStan\Type\Type $type) : bool
    {
        if (!$type instanceof self) {
            return \false;
        }
        if ($this->subtractedType === null) {
            if ($type->subtractedType === null) {
                return \true;
            }
            return \false;
        }
        if ($type->subtractedType === null) {
            return \false;
        }
        return $this->subtractedType->equals($type->subtractedType);
    }
    public function isSubTypeOf(\PHPStan\Type\Type $otherType) : TrinaryLogic
    {
        if ($otherType instanceof self && !$otherType instanceof TemplateMixedType) {
            return TrinaryLogic::createYes();
        }
        if ($this->subtractedType !== null) {
            $isSuperType = $this->subtractedType->isSuperTypeOf($otherType);
            if ($isSuperType->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isAcceptedBy(\PHPStan\Type\Type $acceptingType, bool $strictTypes) : TrinaryLogic
    {
        return $this->isAcceptedWithReasonBy($acceptingType, $strictTypes)->result;
    }
    public function isAcceptedWithReasonBy(\PHPStan\Type\Type $acceptingType, bool $strictTypes) : \PHPStan\Type\AcceptsResult
    {
        $isSuperType = new \PHPStan\Type\AcceptsResult($this->isSuperTypeOf($acceptingType), []);
        if ($isSuperType->no()) {
            return $isSuperType;
        }
        return \PHPStan\Type\AcceptsResult::createYes();
    }
    public function getTemplateType(string $ancestorClassName, string $templateTypeName) : \PHPStan\Type\Type
    {
        return new self();
    }
    public function isObject() : TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new \PHPStan\Type\ObjectWithoutClassType())->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isEnum() : TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new \PHPStan\Type\ObjectWithoutClassType())->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function canAccessProperties() : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function hasProperty(string $propertyName) : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope) : PropertyReflection
    {
        return $this->getUnresolvedPropertyPrototype($propertyName, $scope)->getTransformedProperty();
    }
    public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope) : UnresolvedPropertyPrototypeReflection
    {
        $property = new DummyPropertyReflection();
        return new CallbackUnresolvedPropertyPrototypeReflection($property, $property->getDeclaringClass(), \false, static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type;
        });
    }
    public function canCallMethods() : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function hasMethod(string $methodName) : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope) : ExtendedMethodReflection
    {
        return $this->getUnresolvedMethodPrototype($methodName, $scope)->getTransformedMethod();
    }
    public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope) : UnresolvedMethodPrototypeReflection
    {
        $method = new DummyMethodReflection($methodName);
        return new CallbackUnresolvedMethodPrototypeReflection($method, $method->getDeclaringClass(), \false, static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
            return $type;
        });
    }
    public function canAccessConstants() : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function hasConstant(string $constantName) : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function getConstant(string $constantName) : ConstantReflection
    {
        return new DummyConstantReflection($constantName);
    }
    public function isCloneable() : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function describe(\PHPStan\Type\VerbosityLevel $level) : string
    {
        return $level->handle(static function () : string {
            return 'mixed';
        }, static function () : string {
            return 'mixed';
        }, function () use($level) : string {
            $description = 'mixed';
            if ($this->subtractedType !== null) {
                $description .= sprintf('~%s', $this->subtractedType->describe($level));
            }
            return $description;
        }, function () use($level) : string {
            $description = 'mixed';
            if ($this->subtractedType !== null) {
                $description .= sprintf('~%s', $this->subtractedType->describe($level));
            }
            if ($this->isExplicitMixed) {
                $description .= '=explicit';
            } else {
                $description .= '=implicit';
            }
            return $description;
        });
    }
    public function toBoolean() : \PHPStan\Type\BooleanType
    {
        if ($this->subtractedType !== null && \PHPStan\Type\StaticTypeFactory::falsey()->equals($this->subtractedType)) {
            return new ConstantBooleanType(\true);
        }
        return new \PHPStan\Type\BooleanType();
    }
    public function toNumber() : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\UnionType([$this->toInteger(), $this->toFloat()]);
    }
    public function toInteger() : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\IntegerType();
    }
    public function toFloat() : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\FloatType();
    }
    public function toString() : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\StringType();
    }
    public function toArray() : \PHPStan\Type\Type
    {
        $mixed = new self($this->isExplicitMixed);
        return new \PHPStan\Type\ArrayType($mixed, $mixed);
    }
    public function toArrayKey() : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\UnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]);
    }
    public function isIterable() : TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new \PHPStan\Type\IterableType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()))->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isIterableAtLeastOnce() : TrinaryLogic
    {
        return $this->isIterable();
    }
    public function getArraySize() : \PHPStan\Type\Type
    {
        if ($this->isIterable()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return \PHPStan\Type\IntegerRangeType::fromInterval(0, null);
    }
    public function getIterableKeyType() : \PHPStan\Type\Type
    {
        return new self($this->isExplicitMixed);
    }
    public function getFirstIterableKeyType() : \PHPStan\Type\Type
    {
        return new self($this->isExplicitMixed);
    }
    public function getLastIterableKeyType() : \PHPStan\Type\Type
    {
        return new self($this->isExplicitMixed);
    }
    public function getIterableValueType() : \PHPStan\Type\Type
    {
        return new self($this->isExplicitMixed);
    }
    public function getFirstIterableValueType() : \PHPStan\Type\Type
    {
        return new self($this->isExplicitMixed);
    }
    public function getLastIterableValueType() : \PHPStan\Type\Type
    {
        return new self($this->isExplicitMixed);
    }
    public function isOffsetAccessible() : TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            $offsetAccessibles = new \PHPStan\Type\UnionType([new \PHPStan\Type\StringType(), new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()), new \PHPStan\Type\ObjectType(ArrayAccess::class)]);
            if ($this->subtractedType->isSuperTypeOf($offsetAccessibles)->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function hasOffsetValueType(\PHPStan\Type\Type $offsetType) : TrinaryLogic
    {
        if ($this->isOffsetAccessible()->no()) {
            return TrinaryLogic::createNo();
        }
        return TrinaryLogic::createMaybe();
    }
    public function getOffsetValueType(\PHPStan\Type\Type $offsetType) : \PHPStan\Type\Type
    {
        return new self($this->isExplicitMixed);
    }
    public function isExplicitMixed() : bool
    {
        return $this->isExplicitMixed;
    }
    public function subtract(\PHPStan\Type\Type $type) : \PHPStan\Type\Type
    {
        if ($type instanceof self && !$type instanceof TemplateType) {
            return new \PHPStan\Type\NeverType();
        }
        if ($this->subtractedType !== null) {
            $type = \PHPStan\Type\TypeCombinator::union($this->subtractedType, $type);
        }
        return new self($this->isExplicitMixed, $type);
    }
    public function getTypeWithoutSubtractedType() : \PHPStan\Type\Type
    {
        return new self($this->isExplicitMixed);
    }
    public function changeSubtractedType(?\PHPStan\Type\Type $subtractedType) : \PHPStan\Type\Type
    {
        return new self($this->isExplicitMixed, $subtractedType);
    }
    public function getSubtractedType() : ?\PHPStan\Type\Type
    {
        return $this->subtractedType;
    }
    public function traverse(callable $cb) : \PHPStan\Type\Type
    {
        return $this;
    }
    public function traverseSimultaneously(\PHPStan\Type\Type $right, callable $cb) : \PHPStan\Type\Type
    {
        return $this;
    }
    public function isArray() : TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()))->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isConstantArray() : TrinaryLogic
    {
        return $this->isArray();
    }
    public function isOversizedArray() : TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            $oversizedArray = \PHPStan\Type\TypeCombinator::intersect(new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()), new OversizedArrayType());
            if ($this->subtractedType->isSuperTypeOf($oversizedArray)->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isList() : TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            $list = \PHPStan\Type\TypeCombinator::intersect(new \PHPStan\Type\ArrayType(new \PHPStan\Type\IntegerType(), new \PHPStan\Type\MixedType()), new AccessoryArrayListType());
            if ($this->subtractedType->isSuperTypeOf($list)->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isNull() : TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new \PHPStan\Type\NullType())->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
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
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new ConstantBooleanType(\true))->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isFalse() : TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new ConstantBooleanType(\false))->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isBoolean() : TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new \PHPStan\Type\BooleanType())->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isFloat() : TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new \PHPStan\Type\FloatType())->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isInteger() : TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new \PHPStan\Type\IntegerType())->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isString() : TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new \PHPStan\Type\StringType())->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isNumericString() : TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            $numericString = \PHPStan\Type\TypeCombinator::intersect(new \PHPStan\Type\StringType(), new AccessoryNumericStringType());
            if ($this->subtractedType->isSuperTypeOf($numericString)->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isNonEmptyString() : TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            $nonEmptyString = \PHPStan\Type\TypeCombinator::intersect(new \PHPStan\Type\StringType(), new AccessoryNonEmptyStringType());
            if ($this->subtractedType->isSuperTypeOf($nonEmptyString)->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isNonFalsyString() : TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            $nonFalsyString = \PHPStan\Type\TypeCombinator::intersect(new \PHPStan\Type\StringType(), new AccessoryNonFalsyStringType());
            if ($this->subtractedType->isSuperTypeOf($nonFalsyString)->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isLiteralString() : TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            $literalString = \PHPStan\Type\TypeCombinator::intersect(new \PHPStan\Type\StringType(), new AccessoryLiteralStringType());
            if ($this->subtractedType->isSuperTypeOf($literalString)->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isClassStringType() : TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new \PHPStan\Type\StringType())->yes()) {
                return TrinaryLogic::createNo();
            }
            if ($this->subtractedType->isSuperTypeOf(new \PHPStan\Type\ClassStringType())->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function getClassStringObjectType() : \PHPStan\Type\Type
    {
        if (!$this->isClassStringType()->no()) {
            return new \PHPStan\Type\ObjectWithoutClassType();
        }
        return new \PHPStan\Type\ErrorType();
    }
    public function getObjectTypeOrClassStringObjectType() : \PHPStan\Type\Type
    {
        $objectOrClass = new \PHPStan\Type\UnionType([new \PHPStan\Type\ObjectWithoutClassType(), new \PHPStan\Type\ClassStringType()]);
        if (!$this->isSuperTypeOf($objectOrClass)->no()) {
            return new \PHPStan\Type\ObjectWithoutClassType();
        }
        return new \PHPStan\Type\ErrorType();
    }
    public function isVoid() : TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new \PHPStan\Type\VoidType())->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function isScalar() : TrinaryLogic
    {
        if ($this->subtractedType !== null) {
            if ($this->subtractedType->isSuperTypeOf(new \PHPStan\Type\UnionType([new \PHPStan\Type\BooleanType(), new \PHPStan\Type\FloatType(), new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]))->yes()) {
                return TrinaryLogic::createNo();
            }
        }
        return TrinaryLogic::createMaybe();
    }
    public function looseCompare(\PHPStan\Type\Type $type, PhpVersion $phpVersion) : \PHPStan\Type\BooleanType
    {
        return new \PHPStan\Type\BooleanType();
    }
    public function tryRemove(\PHPStan\Type\Type $typeToRemove) : ?\PHPStan\Type\Type
    {
        if ($this->isSuperTypeOf($typeToRemove)->yes()) {
            return $this->subtract($typeToRemove);
        }
        return null;
    }
    public function exponentiate(\PHPStan\Type\Type $exponent) : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\BenevolentUnionType([new \PHPStan\Type\FloatType(), new \PHPStan\Type\IntegerType()]);
    }
    public function getFiniteTypes() : array
    {
        return [];
    }
    public function toPhpDocNode() : TypeNode
    {
        return new IdentifierTypeNode('mixed');
    }
    /**
     * @param mixed[] $properties
     */
    public static function __set_state(array $properties) : \PHPStan\Type\Type
    {
        return new self($properties['isExplicitMixed'], $properties['subtractedType'] ?? null);
    }
}