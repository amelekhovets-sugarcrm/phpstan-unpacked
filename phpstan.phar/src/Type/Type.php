<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeReference;
use PHPStan\Type\Generic\TemplateTypeVariance;
/**
 * @api
 * @see https://phpstan.org/developing-extensions/type-system
 */
interface Type
{
    /**
     * @return string[]
     */
    public function getReferencedClasses() : array;
    /** @return list<string> */
    public function getObjectClassNames() : array;
    /**
     * @return list<ClassReflection>
     */
    public function getObjectClassReflections() : array;
    /**
     * Returns object type Foo for class-string<Foo> and 'Foo' (if Foo is a valid class).
     */
    public function getClassStringObjectType() : \PHPStan\Type\Type;
    /**
     * Returns object type Foo for class-string<Foo>, 'Foo' (if Foo is a valid class),
     * and object type Foo.
     */
    public function getObjectTypeOrClassStringObjectType() : \PHPStan\Type\Type;
    public function isObject() : TrinaryLogic;
    public function isEnum() : TrinaryLogic;
    /** @return list<ArrayType> */
    public function getArrays() : array;
    /** @return list<ConstantArrayType> */
    public function getConstantArrays() : array;
    /** @return list<ConstantStringType> */
    public function getConstantStrings() : array;
    public function accepts(\PHPStan\Type\Type $type, bool $strictTypes) : TrinaryLogic;
    /**
     * This is like accepts() but gives reasons
     * why the type was not/might not be accepted in some non-intuitive scenarios.
     *
     * In PHPStan 2.0 this method will be removed and the return type of accepts()
     * will change to AcceptsResult.
     */
    public function acceptsWithReason(\PHPStan\Type\Type $type, bool $strictTypes) : \PHPStan\Type\AcceptsResult;
    public function isSuperTypeOf(\PHPStan\Type\Type $type) : TrinaryLogic;
    public function equals(\PHPStan\Type\Type $type) : bool;
    public function describe(\PHPStan\Type\VerbosityLevel $level) : string;
    public function canAccessProperties() : TrinaryLogic;
    public function hasProperty(string $propertyName) : TrinaryLogic;
    public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope) : PropertyReflection;
    public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope) : UnresolvedPropertyPrototypeReflection;
    public function canCallMethods() : TrinaryLogic;
    public function hasMethod(string $methodName) : TrinaryLogic;
    public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope) : ExtendedMethodReflection;
    public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope) : UnresolvedMethodPrototypeReflection;
    public function canAccessConstants() : TrinaryLogic;
    public function hasConstant(string $constantName) : TrinaryLogic;
    public function getConstant(string $constantName) : ConstantReflection;
    public function isIterable() : TrinaryLogic;
    public function isIterableAtLeastOnce() : TrinaryLogic;
    public function getArraySize() : \PHPStan\Type\Type;
    public function getIterableKeyType() : \PHPStan\Type\Type;
    public function getFirstIterableKeyType() : \PHPStan\Type\Type;
    public function getLastIterableKeyType() : \PHPStan\Type\Type;
    public function getIterableValueType() : \PHPStan\Type\Type;
    public function getFirstIterableValueType() : \PHPStan\Type\Type;
    public function getLastIterableValueType() : \PHPStan\Type\Type;
    public function isArray() : TrinaryLogic;
    public function isConstantArray() : TrinaryLogic;
    public function isOversizedArray() : TrinaryLogic;
    public function isList() : TrinaryLogic;
    public function isOffsetAccessible() : TrinaryLogic;
    public function hasOffsetValueType(\PHPStan\Type\Type $offsetType) : TrinaryLogic;
    public function getOffsetValueType(\PHPStan\Type\Type $offsetType) : \PHPStan\Type\Type;
    public function setOffsetValueType(?\PHPStan\Type\Type $offsetType, \PHPStan\Type\Type $valueType, bool $unionValues = \true) : \PHPStan\Type\Type;
    public function unsetOffset(\PHPStan\Type\Type $offsetType) : \PHPStan\Type\Type;
    public function getKeysArray() : \PHPStan\Type\Type;
    public function getValuesArray() : \PHPStan\Type\Type;
    public function fillKeysArray(\PHPStan\Type\Type $valueType) : \PHPStan\Type\Type;
    public function flipArray() : \PHPStan\Type\Type;
    public function intersectKeyArray(\PHPStan\Type\Type $otherArraysType) : \PHPStan\Type\Type;
    public function popArray() : \PHPStan\Type\Type;
    public function searchArray(\PHPStan\Type\Type $needleType) : \PHPStan\Type\Type;
    public function shiftArray() : \PHPStan\Type\Type;
    public function shuffleArray() : \PHPStan\Type\Type;
    /**
     * @return list<EnumCaseObjectType>
     */
    public function getEnumCases() : array;
    /**
     * Returns a list of finite values.
     *
     * Examples:
     *
     * - for bool: [true, false]
     * - for int<0, 3>: [0, 1, 2, 3]
     * - for enums: list of enum cases
     * - for scalars: the scalar itself
     *
     * For infinite types it returns an empty array.
     *
     * @return list<Type>
     */
    public function getFiniteTypes() : array;
    public function exponentiate(\PHPStan\Type\Type $exponent) : \PHPStan\Type\Type;
    public function isCallable() : TrinaryLogic;
    /**
     * @return ParametersAcceptor[]
     */
    public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope) : array;
    public function isCloneable() : TrinaryLogic;
    public function toBoolean() : \PHPStan\Type\BooleanType;
    public function toNumber() : \PHPStan\Type\Type;
    public function toInteger() : \PHPStan\Type\Type;
    public function toFloat() : \PHPStan\Type\Type;
    public function toString() : \PHPStan\Type\Type;
    public function toArray() : \PHPStan\Type\Type;
    public function toArrayKey() : \PHPStan\Type\Type;
    public function isSmallerThan(\PHPStan\Type\Type $otherType) : TrinaryLogic;
    public function isSmallerThanOrEqual(\PHPStan\Type\Type $otherType) : TrinaryLogic;
    /**
     * Is Type of a known constant value? Includes literal strings, integers, floats, true, false, null, and array shapes.
     */
    public function isConstantValue() : TrinaryLogic;
    /**
     * Is Type of a known constant scalar value? Includes literal strings, integers, floats, true, false, and null.
     */
    public function isConstantScalarValue() : TrinaryLogic;
    /**
     * @return list<ConstantScalarType>
     */
    public function getConstantScalarTypes() : array;
    /**
     * @return list<int|float|string|bool|null>
     */
    public function getConstantScalarValues() : array;
    public function isNull() : TrinaryLogic;
    public function isTrue() : TrinaryLogic;
    public function isFalse() : TrinaryLogic;
    public function isBoolean() : TrinaryLogic;
    public function isFloat() : TrinaryLogic;
    public function isInteger() : TrinaryLogic;
    public function isString() : TrinaryLogic;
    public function isNumericString() : TrinaryLogic;
    public function isNonEmptyString() : TrinaryLogic;
    public function isNonFalsyString() : TrinaryLogic;
    public function isLiteralString() : TrinaryLogic;
    public function isClassStringType() : TrinaryLogic;
    public function isVoid() : TrinaryLogic;
    public function isScalar() : TrinaryLogic;
    public function looseCompare(\PHPStan\Type\Type $type, PhpVersion $phpVersion) : \PHPStan\Type\BooleanType;
    public function getSmallerType() : \PHPStan\Type\Type;
    public function getSmallerOrEqualType() : \PHPStan\Type\Type;
    public function getGreaterType() : \PHPStan\Type\Type;
    public function getGreaterOrEqualType() : \PHPStan\Type\Type;
    /**
     * Returns actual template type for a given object.
     *
     * Example:
     *
     * @-template T
     * class Foo {}
     *
     * // $fooType is Foo<int>
     * $t = $fooType->getTemplateType(Foo::class, 'T');
     * $t->isInteger(); // yes
     *
     * Returns ErrorType in case of a missing type.
     *
     * @param class-string $ancestorClassName
     */
    public function getTemplateType(string $ancestorClassName, string $templateTypeName) : \PHPStan\Type\Type;
    /**
     * Infers template types
     *
     * Infers the real Type of the TemplateTypes found in $this, based on
     * the received Type.
     */
    public function inferTemplateTypes(\PHPStan\Type\Type $receivedType) : TemplateTypeMap;
    /**
     * Returns the template types referenced by this Type, recursively
     *
     * The return value is a list of TemplateTypeReferences, who contain the
     * referenced template type as well as the variance position in which it was
     * found.
     *
     * For example, calling this on array<Foo<T>,Bar> (with T a template type)
     * will return one TemplateTypeReference for the type T.
     *
     * @param TemplateTypeVariance $positionVariance The variance position in
     *                                               which the receiver type was
     *                                               found.
     *
     * @return TemplateTypeReference[]
     */
    public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance) : array;
    /**
     * Traverses inner types
     *
     * Returns a new instance with all inner types mapped through $cb. Might
     * return the same instance if inner types did not change.
     *
     * @param callable(Type):Type $cb
     */
    public function traverse(callable $cb) : \PHPStan\Type\Type;
    /**
     * Traverses inner types while keeping the same context in another type.
     *
     * @param callable(Type $left, Type $right): Type $cb
     */
    public function traverseSimultaneously(\PHPStan\Type\Type $right, callable $cb) : \PHPStan\Type\Type;
    public function toPhpDocNode() : TypeNode;
    /**
     * Return the difference with another type, or null if it cannot be represented.
     *
     * @see TypeCombinator::remove()
     */
    public function tryRemove(\PHPStan\Type\Type $typeToRemove) : ?\PHPStan\Type\Type;
    public function generalize(\PHPStan\Type\GeneralizePrecision $precision) : \PHPStan\Type\Type;
    /**
     * @param mixed[] $properties
     */
    public static function __set_state(array $properties) : self;
}
