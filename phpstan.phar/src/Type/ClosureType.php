<?php

declare (strict_types=1);
namespace PHPStan\Type;

use Closure;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeParameterNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Printer\Printer;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Reflection\Php\ClosureCallUnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Php\DummyParameter;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\Traits\NonArrayTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\NonIterableTypeTrait;
use PHPStan\Type\Traits\NonOffsetAccessibleTypeTrait;
use PHPStan\Type\Traits\NonRemoveableTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonTypeTrait;
use function array_map;
use function array_merge;
use function count;
/** @api */
class ClosureType implements \PHPStan\Type\TypeWithClassName, ParametersAcceptor
{
    /**
     * @var array<int, ParameterReflection>
     */
    private $parameters;
    /**
     * @var \PHPStan\Type\Type
     */
    private $returnType;
    /**
     * @var bool
     */
    private $variadic;
    use NonArrayTypeTrait;
    use NonGenericTypeTrait;
    use NonIterableTypeTrait;
    use UndecidedComparisonTypeTrait;
    use NonOffsetAccessibleTypeTrait;
    use NonRemoveableTypeTrait;
    use NonGeneralizableTypeTrait;
    /**
     * @var \PHPStan\Type\ObjectType
     */
    private $objectType;
    /**
     * @var \PHPStan\Type\Generic\TemplateTypeMap
     */
    private $templateTypeMap;
    /**
     * @var \PHPStan\Type\Generic\TemplateTypeMap
     */
    private $resolvedTemplateTypeMap;
    /**
     * @var \PHPStan\Type\Generic\TemplateTypeVarianceMap
     */
    private $callSiteVarianceMap;
    /**
     * @api
     * @param array<int, ParameterReflection> $parameters
     */
    public function __construct(array $parameters, \PHPStan\Type\Type $returnType, bool $variadic, ?TemplateTypeMap $templateTypeMap = null, ?TemplateTypeMap $resolvedTemplateTypeMap = null, ?TemplateTypeVarianceMap $callSiteVarianceMap = null)
    {
        $this->parameters = $parameters;
        $this->returnType = $returnType;
        $this->variadic = $variadic;
        $this->objectType = new \PHPStan\Type\ObjectType(Closure::class);
        $this->templateTypeMap = $templateTypeMap ?? TemplateTypeMap::createEmpty();
        $this->resolvedTemplateTypeMap = $resolvedTemplateTypeMap ?? TemplateTypeMap::createEmpty();
        $this->callSiteVarianceMap = $callSiteVarianceMap ?? TemplateTypeVarianceMap::createEmpty();
    }
    public function getClassName() : string
    {
        return $this->objectType->getClassName();
    }
    public function getClassReflection() : ?ClassReflection
    {
        return $this->objectType->getClassReflection();
    }
    public function getAncestorWithClassName(string $className) : ?\PHPStan\Type\TypeWithClassName
    {
        return $this->objectType->getAncestorWithClassName($className);
    }
    /**
     * @return string[]
     */
    public function getReferencedClasses() : array
    {
        $classes = $this->objectType->getReferencedClasses();
        foreach ($this->parameters as $parameter) {
            $classes = array_merge($classes, $parameter->getType()->getReferencedClasses());
        }
        return array_merge($classes, $this->returnType->getReferencedClasses());
    }
    public function getObjectClassNames() : array
    {
        return $this->objectType->getObjectClassNames();
    }
    public function getObjectClassReflections() : array
    {
        return $this->objectType->getObjectClassReflections();
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
        if (!$type instanceof \PHPStan\Type\ClosureType) {
            return $this->objectType->acceptsWithReason($type, $strictTypes);
        }
        return $this->isSuperTypeOfInternal($type, \true);
    }
    public function isSuperTypeOf(\PHPStan\Type\Type $type) : TrinaryLogic
    {
        if ($type instanceof \PHPStan\Type\CompoundType) {
            return $type->isSubTypeOf($this);
        }
        return $this->isSuperTypeOfInternal($type, \false)->result;
    }
    private function isSuperTypeOfInternal(\PHPStan\Type\Type $type, bool $treatMixedAsAny) : \PHPStan\Type\AcceptsResult
    {
        if ($type instanceof self) {
            return \PHPStan\Type\CallableTypeHelper::isParametersAcceptorSuperTypeOf($this, $type, $treatMixedAsAny);
        }
        if ($type->getObjectClassNames() === [Closure::class]) {
            return \PHPStan\Type\AcceptsResult::createMaybe();
        }
        return new \PHPStan\Type\AcceptsResult($this->objectType->isSuperTypeOf($type), []);
    }
    public function equals(\PHPStan\Type\Type $type) : bool
    {
        if (!$type instanceof self) {
            return \false;
        }
        return $this->returnType->equals($type->returnType);
    }
    public function describe(\PHPStan\Type\VerbosityLevel $level) : string
    {
        return $level->handle(static function () : string {
            return 'Closure';
        }, function () : string {
            $printer = new Printer();
            $selfWithoutParameterNames = new self(array_map(static function (ParameterReflection $p) : ParameterReflection {
                return new DummyParameter('', $p->getType(), $p->isOptional() && !$p->isVariadic(), PassedByReference::createNo(), $p->isVariadic(), $p->getDefaultValue());
            }, $this->parameters), $this->returnType, $this->variadic, $this->templateTypeMap, $this->resolvedTemplateTypeMap, $this->callSiteVarianceMap);
            return $printer->print($selfWithoutParameterNames->toPhpDocNode());
        });
    }
    public function isObject() : TrinaryLogic
    {
        return $this->objectType->isObject();
    }
    public function isEnum() : TrinaryLogic
    {
        return $this->objectType->isEnum();
    }
    public function getTemplateType(string $ancestorClassName, string $templateTypeName) : \PHPStan\Type\Type
    {
        return $this->objectType->getTemplateType($ancestorClassName, $templateTypeName);
    }
    public function canAccessProperties() : TrinaryLogic
    {
        return $this->objectType->canAccessProperties();
    }
    public function hasProperty(string $propertyName) : TrinaryLogic
    {
        return $this->objectType->hasProperty($propertyName);
    }
    public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope) : PropertyReflection
    {
        return $this->objectType->getProperty($propertyName, $scope);
    }
    public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope) : UnresolvedPropertyPrototypeReflection
    {
        return $this->objectType->getUnresolvedPropertyPrototype($propertyName, $scope);
    }
    public function canCallMethods() : TrinaryLogic
    {
        return $this->objectType->canCallMethods();
    }
    public function hasMethod(string $methodName) : TrinaryLogic
    {
        return $this->objectType->hasMethod($methodName);
    }
    public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope) : ExtendedMethodReflection
    {
        return $this->getUnresolvedMethodPrototype($methodName, $scope)->getTransformedMethod();
    }
    public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope) : UnresolvedMethodPrototypeReflection
    {
        if ($methodName === 'call') {
            return new ClosureCallUnresolvedMethodPrototypeReflection($this->objectType->getUnresolvedMethodPrototype($methodName, $scope), $this);
        }
        return $this->objectType->getUnresolvedMethodPrototype($methodName, $scope);
    }
    public function canAccessConstants() : TrinaryLogic
    {
        return $this->objectType->canAccessConstants();
    }
    public function hasConstant(string $constantName) : TrinaryLogic
    {
        return $this->objectType->hasConstant($constantName);
    }
    public function getConstant(string $constantName) : ConstantReflection
    {
        return $this->objectType->getConstant($constantName);
    }
    public function getConstantStrings() : array
    {
        return [];
    }
    public function isIterable() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isIterableAtLeastOnce() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isCallable() : TrinaryLogic
    {
        return TrinaryLogic::createYes();
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
        return [$this];
    }
    public function isCloneable() : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function toBoolean() : \PHPStan\Type\BooleanType
    {
        return new ConstantBooleanType(\true);
    }
    public function toNumber() : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function toInteger() : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function toFloat() : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function toString() : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function toArray() : \PHPStan\Type\Type
    {
        return new ConstantArrayType([new ConstantIntegerType(0)], [$this], [1], [], TrinaryLogic::createYes());
    }
    public function toArrayKey() : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function getTemplateTypeMap() : TemplateTypeMap
    {
        return $this->templateTypeMap;
    }
    public function getResolvedTemplateTypeMap() : TemplateTypeMap
    {
        return $this->resolvedTemplateTypeMap;
    }
    public function getCallSiteVarianceMap() : TemplateTypeVarianceMap
    {
        return $this->callSiteVarianceMap;
    }
    /**
     * @return array<int, ParameterReflection>
     */
    public function getParameters() : array
    {
        return $this->parameters;
    }
    public function isVariadic() : bool
    {
        return $this->variadic;
    }
    public function getReturnType() : \PHPStan\Type\Type
    {
        return $this->returnType;
    }
    public function inferTemplateTypes(\PHPStan\Type\Type $receivedType) : TemplateTypeMap
    {
        if ($receivedType instanceof \PHPStan\Type\UnionType || $receivedType instanceof \PHPStan\Type\IntersectionType) {
            return $receivedType->inferTemplateTypesOn($this);
        }
        if ($receivedType->isCallable()->no() || !$receivedType instanceof self) {
            return TemplateTypeMap::createEmpty();
        }
        $parametersAcceptors = $receivedType->getCallableParametersAcceptors(new OutOfClassScope());
        $typeMap = TemplateTypeMap::createEmpty();
        foreach ($parametersAcceptors as $parametersAcceptor) {
            $typeMap = $typeMap->union($this->inferTemplateTypesOnParametersAcceptor($parametersAcceptor));
        }
        return $typeMap;
    }
    private function inferTemplateTypesOnParametersAcceptor(ParametersAcceptor $parametersAcceptor) : TemplateTypeMap
    {
        $typeMap = TemplateTypeMap::createEmpty();
        $args = $parametersAcceptor->getParameters();
        $returnType = $parametersAcceptor->getReturnType();
        foreach ($this->getParameters() as $i => $param) {
            $paramType = $param->getType();
            if (isset($args[$i])) {
                $argType = $args[$i]->getType();
            } elseif ($paramType instanceof TemplateType) {
                $argType = TemplateTypeHelper::resolveToBounds($paramType);
            } else {
                $argType = new \PHPStan\Type\NeverType();
            }
            $typeMap = $typeMap->union($paramType->inferTemplateTypes($argType)->convertToLowerBoundTypes());
        }
        return $typeMap->union($this->getReturnType()->inferTemplateTypes($returnType));
    }
    public function traverse(callable $cb) : \PHPStan\Type\Type
    {
        return new self(array_map(static function (ParameterReflection $param) use($cb) : NativeParameterReflection {
            $defaultValue = $param->getDefaultValue();
            return new NativeParameterReflection($param->getName(), $param->isOptional(), $cb($param->getType()), $param->passedByReference(), $param->isVariadic(), $defaultValue !== null ? $cb($defaultValue) : null);
        }, $this->getParameters()), $cb($this->getReturnType()), $this->isVariadic(), $this->templateTypeMap, $this->resolvedTemplateTypeMap, $this->callSiteVarianceMap);
    }
    public function traverseSimultaneously(\PHPStan\Type\Type $right, callable $cb) : \PHPStan\Type\Type
    {
        if (!$right instanceof self) {
            return $this;
        }
        $rightParameters = $right->getParameters();
        if (count($this->getParameters()) !== count($rightParameters)) {
            return $this;
        }
        $parameters = [];
        foreach ($this->getParameters() as $i => $leftParam) {
            $rightParam = $rightParameters[$i];
            $leftDefaultValue = $leftParam->getDefaultValue();
            $rightDefaultValue = $rightParam->getDefaultValue();
            $defaultValue = $leftDefaultValue;
            if ($leftDefaultValue !== null && $rightDefaultValue !== null) {
                $defaultValue = $cb($leftDefaultValue, $rightDefaultValue);
            }
            $parameters[] = new NativeParameterReflection($leftParam->getName(), $leftParam->isOptional(), $cb($leftParam->getType(), $rightParam->getType()), $leftParam->passedByReference(), $leftParam->isVariadic(), $defaultValue);
        }
        return new self($parameters, $cb($this->getReturnType(), $right->getReturnType()), $this->isVariadic());
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
        return $this;
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
    public function exponentiate(\PHPStan\Type\Type $exponent) : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function getFiniteTypes() : array
    {
        return [];
    }
    public function toPhpDocNode() : TypeNode
    {
        $parameters = [];
        foreach ($this->parameters as $parameter) {
            $parameters[] = new CallableTypeParameterNode($parameter->getType()->toPhpDocNode(), !$parameter->passedByReference()->no(), $parameter->isVariadic(), $parameter->getName() === '' ? '' : '$' . $parameter->getName(), $parameter->isOptional());
        }
        return new CallableTypeNode(new IdentifierTypeNode('Closure'), $parameters, $this->returnType->toPhpDocNode());
    }
    /**
     * @param mixed[] $properties
     */
    public static function __set_state(array $properties) : \PHPStan\Type\Type
    {
        return new self($properties['parameters'], $properties['returnType'], $properties['variadic'], $properties['templateTypeMap'], $properties['resolvedTemplateTypeMap'], $properties['callSiteVarianceMap']);
    }
}
