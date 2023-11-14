<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Traits\MaybeCallableTypeTrait;
use PHPStan\Type\Traits\NonArrayTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\NonIterableTypeTrait;
use PHPStan\Type\Traits\NonObjectTypeTrait;
use PHPStan\Type\Traits\UndecidedBooleanTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonTypeTrait;
use function count;
/** @api */
class StringType implements \PHPStan\Type\Type
{
    use \PHPStan\Type\JustNullableTypeTrait;
    use MaybeCallableTypeTrait;
    use NonArrayTypeTrait;
    use NonIterableTypeTrait;
    use NonObjectTypeTrait;
    use UndecidedBooleanTypeTrait;
    use UndecidedComparisonTypeTrait;
    use NonGenericTypeTrait;
    use NonGeneralizableTypeTrait;
    /** @api */
    public function __construct()
    {
    }
    public function describe(\PHPStan\Type\VerbosityLevel $level) : string
    {
        return 'string';
    }
    public function getConstantStrings() : array
    {
        return [];
    }
    public function isOffsetAccessible() : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function hasOffsetValueType(\PHPStan\Type\Type $offsetType) : TrinaryLogic
    {
        return (new \PHPStan\Type\IntegerType())->isSuperTypeOf($offsetType)->and(TrinaryLogic::createMaybe());
    }
    public function getOffsetValueType(\PHPStan\Type\Type $offsetType) : \PHPStan\Type\Type
    {
        if ($this->hasOffsetValueType($offsetType)->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return new \PHPStan\Type\StringType();
    }
    public function setOffsetValueType(?\PHPStan\Type\Type $offsetType, \PHPStan\Type\Type $valueType, bool $unionValues = \true) : \PHPStan\Type\Type
    {
        if ($offsetType === null) {
            return new \PHPStan\Type\ErrorType();
        }
        $valueStringType = $valueType->toString();
        if ($valueStringType instanceof \PHPStan\Type\ErrorType) {
            return new \PHPStan\Type\ErrorType();
        }
        if ((new \PHPStan\Type\IntegerType())->isSuperTypeOf($offsetType)->yes() || $offsetType instanceof \PHPStan\Type\MixedType) {
            return new \PHPStan\Type\StringType();
        }
        return new \PHPStan\Type\ErrorType();
    }
    public function unsetOffset(\PHPStan\Type\Type $offsetType) : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function accepts(\PHPStan\Type\Type $type, bool $strictTypes) : TrinaryLogic
    {
        return $this->acceptsWithReason($type, $strictTypes)->result;
    }
    public function acceptsWithReason(\PHPStan\Type\Type $type, bool $strictTypes) : \PHPStan\Type\AcceptsResult
    {
        if ($type instanceof self) {
            return \PHPStan\Type\AcceptsResult::createYes();
        }
        if ($type instanceof \PHPStan\Type\CompoundType) {
            return $type->isAcceptedWithReasonBy($this, $strictTypes);
        }
        $thatClassNames = $type->getObjectClassNames();
        if (count($thatClassNames) > 1) {
            throw new ShouldNotHappenException();
        }
        if ($thatClassNames === [] || $strictTypes) {
            return \PHPStan\Type\AcceptsResult::createNo();
        }
        $reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
        if (!$reflectionProvider->hasClass($thatClassNames[0])) {
            return \PHPStan\Type\AcceptsResult::createNo();
        }
        $typeClass = $reflectionProvider->getClass($thatClassNames[0]);
        return \PHPStan\Type\AcceptsResult::createFromBoolean($typeClass->hasNativeMethod('__toString'));
    }
    public function toNumber() : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
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
        return $this;
    }
    public function toArray() : \PHPStan\Type\Type
    {
        return new ConstantArrayType([new ConstantIntegerType(0)], [$this], [1], [], TrinaryLogic::createYes());
    }
    public function toArrayKey() : \PHPStan\Type\Type
    {
        return $this;
    }
    public function isNull() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
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
        return TrinaryLogic::createYes();
    }
    public function isNumericString() : TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function isNonEmptyString() : TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function isNonFalsyString() : TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function isLiteralString() : TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function isClassStringType() : TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function getClassStringObjectType() : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ObjectWithoutClassType();
    }
    public function getObjectTypeOrClassStringObjectType() : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ObjectWithoutClassType();
    }
    public function isScalar() : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function looseCompare(\PHPStan\Type\Type $type, PhpVersion $phpVersion) : \PHPStan\Type\BooleanType
    {
        return new \PHPStan\Type\BooleanType();
    }
    public function hasMethod(string $methodName) : TrinaryLogic
    {
        if ($this->isClassStringType()->yes()) {
            return TrinaryLogic::createMaybe();
        }
        return TrinaryLogic::createNo();
    }
    public function tryRemove(\PHPStan\Type\Type $typeToRemove) : ?\PHPStan\Type\Type
    {
        if ($typeToRemove instanceof ConstantStringType && $typeToRemove->getValue() === '') {
            return \PHPStan\Type\TypeCombinator::intersect($this, new AccessoryNonEmptyStringType());
        }
        if ($typeToRemove instanceof AccessoryNonEmptyStringType) {
            return new ConstantStringType('');
        }
        return null;
    }
    public function getFiniteTypes() : array
    {
        return [];
    }
    public function exponentiate(\PHPStan\Type\Type $exponent) : \PHPStan\Type\Type
    {
        return \PHPStan\Type\ExponentiateHelper::exponentiate($this, $exponent);
    }
    public function toPhpDocNode() : TypeNode
    {
        return new IdentifierTypeNode('string');
    }
    /**
     * @param mixed[] $properties
     */
    public static function __set_state(array $properties) : \PHPStan\Type\Type
    {
        return new self();
    }
}
