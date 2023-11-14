<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeParameterNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Printer\Printer;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Reflection\Php\DummyParameter;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\Traits\MaybeArrayTypeTrait;
use PHPStan\Type\Traits\MaybeIterableTypeTrait;
use PHPStan\Type\Traits\MaybeObjectTypeTrait;
use PHPStan\Type\Traits\MaybeOffsetAccessibleTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonRemoveableTypeTrait;
use PHPStan\Type\Traits\TruthyBooleanTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use function array_map;
use function array_merge;
use function count;
/** @api */
class CallableType implements \PHPStan\Type\CompoundType, ParametersAcceptor
{
    /**
     * @var bool
     */
    private $variadic = \true;
    use MaybeArrayTypeTrait;
    use MaybeIterableTypeTrait;
    use MaybeObjectTypeTrait;
    use MaybeOffsetAccessibleTypeTrait;
    use TruthyBooleanTypeTrait;
    use UndecidedComparisonCompoundTypeTrait;
    use NonRemoveableTypeTrait;
    use NonGeneralizableTypeTrait;
    /** @var array<int, ParameterReflection> */
    private $parameters;
    /**
     * @var \PHPStan\Type\Type
     */
    private $returnType;
    /**
     * @var bool
     */
    private $isCommonCallable;
    /**
     * @api
     * @param array<int, ParameterReflection>|null $parameters
     */
    public function __construct(?array $parameters = null, ?\PHPStan\Type\Type $returnType = null, bool $variadic = \true)
    {
        $this->variadic = $variadic;
        $this->parameters = $parameters ?? [];
        $this->returnType = $returnType ?? new \PHPStan\Type\MixedType();
        $this->isCommonCallable = $parameters === null && $returnType === null;
    }
    /**
     * @return string[]
     */
    public function getReferencedClasses() : array
    {
        $classes = [];
        foreach ($this->parameters as $parameter) {
            $classes = array_merge($classes, $parameter->getType()->getReferencedClasses());
        }
        return array_merge($classes, $this->returnType->getReferencedClasses());
    }
    public function getObjectClassNames() : array
    {
        return [];
    }
    public function getObjectClassReflections() : array
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
        if ($type instanceof \PHPStan\Type\CompoundType && !$type instanceof self) {
            return $type->isAcceptedWithReasonBy($this, $strictTypes);
        }
        return $this->isSuperTypeOfInternal($type, \true);
    }
    public function isSuperTypeOf(\PHPStan\Type\Type $type) : TrinaryLogic
    {
        if ($type instanceof \PHPStan\Type\CompoundType && !$type instanceof self) {
            return $type->isSubTypeOf($this);
        }
        return $this->isSuperTypeOfInternal($type, \false)->result;
    }
    private function isSuperTypeOfInternal(\PHPStan\Type\Type $type, bool $treatMixedAsAny) : \PHPStan\Type\AcceptsResult
    {
        $isCallable = new \PHPStan\Type\AcceptsResult($type->isCallable(), []);
        if ($isCallable->no() || $this->isCommonCallable) {
            return $isCallable;
        }
        static $scope;
        if ($scope === null) {
            $scope = new OutOfClassScope();
        }
        $variantsResult = null;
        foreach ($type->getCallableParametersAcceptors($scope) as $variant) {
            $isSuperType = \PHPStan\Type\CallableTypeHelper::isParametersAcceptorSuperTypeOf($this, $variant, $treatMixedAsAny);
            if ($variantsResult === null) {
                $variantsResult = $isSuperType;
            } else {
                $variantsResult = $variantsResult->or($isSuperType);
            }
        }
        if ($variantsResult === null) {
            throw new ShouldNotHappenException();
        }
        return $isCallable->and($variantsResult);
    }
    public function isSubTypeOf(\PHPStan\Type\Type $otherType) : TrinaryLogic
    {
        if ($otherType instanceof \PHPStan\Type\IntersectionType || $otherType instanceof \PHPStan\Type\UnionType) {
            return $otherType->isSuperTypeOf($this);
        }
        return $otherType->isCallable()->and($otherType instanceof self ? TrinaryLogic::createYes() : TrinaryLogic::createMaybe());
    }
    public function isAcceptedBy(\PHPStan\Type\Type $acceptingType, bool $strictTypes) : TrinaryLogic
    {
        return $this->isAcceptedWithReasonBy($acceptingType, $strictTypes)->result;
    }
    public function isAcceptedWithReasonBy(\PHPStan\Type\Type $acceptingType, bool $strictTypes) : \PHPStan\Type\AcceptsResult
    {
        return new \PHPStan\Type\AcceptsResult($this->isSubTypeOf($acceptingType), []);
    }
    public function equals(\PHPStan\Type\Type $type) : bool
    {
        return $type instanceof self;
    }
    public function describe(\PHPStan\Type\VerbosityLevel $level) : string
    {
        return $level->handle(static function () : string {
            return 'callable';
        }, function () : string {
            $printer = new Printer();
            $selfWithoutParameterNames = new self(array_map(static function (ParameterReflection $p) : ParameterReflection {
                return new DummyParameter('', $p->getType(), $p->isOptional() && !$p->isVariadic(), PassedByReference::createNo(), $p->isVariadic(), $p->getDefaultValue());
            }, $this->parameters), $this->returnType, $this->variadic);
            return $printer->print($selfWithoutParameterNames->toPhpDocNode());
        });
    }
    public function isCallable() : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    /**
     * @return ParametersAcceptor[]
     */
    public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope) : array
    {
        return [$this];
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
        return new \PHPStan\Type\ErrorType();
    }
    public function toFloat() : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function toArray() : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType());
    }
    public function toArrayKey() : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function getTemplateTypeMap() : TemplateTypeMap
    {
        return TemplateTypeMap::createEmpty();
    }
    public function getResolvedTemplateTypeMap() : TemplateTypeMap
    {
        return TemplateTypeMap::createEmpty();
    }
    public function getCallSiteVarianceMap() : TemplateTypeVarianceMap
    {
        return TemplateTypeVarianceMap::createEmpty();
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
        if (!$receivedType->isCallable()->yes()) {
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
    public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance) : array
    {
        $references = $this->getReturnType()->getReferencedTemplateTypes($positionVariance->compose(TemplateTypeVariance::createCovariant()));
        $paramVariance = $positionVariance->compose(TemplateTypeVariance::createContravariant());
        foreach ($this->getParameters() as $param) {
            foreach ($param->getType()->getReferencedTemplateTypes($paramVariance) as $reference) {
                $references[] = $reference;
            }
        }
        return $references;
    }
    public function traverse(callable $cb) : \PHPStan\Type\Type
    {
        if ($this->isCommonCallable) {
            return $this;
        }
        $parameters = array_map(static function (ParameterReflection $param) use($cb) : NativeParameterReflection {
            $defaultValue = $param->getDefaultValue();
            return new NativeParameterReflection($param->getName(), $param->isOptional(), $cb($param->getType()), $param->passedByReference(), $param->isVariadic(), $defaultValue !== null ? $cb($defaultValue) : null);
        }, $this->getParameters());
        return new self($parameters, $cb($this->getReturnType()), $this->isVariadic());
    }
    public function traverseSimultaneously(\PHPStan\Type\Type $right, callable $cb) : \PHPStan\Type\Type
    {
        if ($this->isCommonCallable) {
            return $this;
        }
        if (!$right->isCallable()->yes()) {
            return $this;
        }
        $rightAcceptors = $right->getCallableParametersAcceptors(new OutOfClassScope());
        if (count($rightAcceptors) !== 1) {
            return $this;
        }
        $rightParameters = $rightAcceptors[0]->getParameters();
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
        return new self($parameters, $cb($this->getReturnType(), $rightAcceptors[0]->getReturnType()), $this->isVariadic());
    }
    public function isOversizedArray() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
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
        return TrinaryLogic::createMaybe();
    }
    public function isNumericString() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
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
    public function isVoid() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isScalar() : TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function looseCompare(\PHPStan\Type\Type $type, PhpVersion $phpVersion) : \PHPStan\Type\BooleanType
    {
        return new \PHPStan\Type\BooleanType();
    }
    public function getEnumCases() : array
    {
        return [];
    }
    public function isCommonCallable() : bool
    {
        return $this->isCommonCallable;
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
        if ($this->isCommonCallable) {
            return new IdentifierTypeNode('callable');
        }
        $parameters = [];
        foreach ($this->parameters as $parameter) {
            $parameters[] = new CallableTypeParameterNode($parameter->getType()->toPhpDocNode(), !$parameter->passedByReference()->no(), $parameter->isVariadic(), $parameter->getName() === '' ? '' : '$' . $parameter->getName(), $parameter->isOptional());
        }
        return new CallableTypeNode(new IdentifierTypeNode('callable'), $parameters, $this->returnType->toPhpDocNode());
    }
    /**
     * @param mixed[] $properties
     */
    public static function __set_state(array $properties) : \PHPStan\Type\Type
    {
        return new self((bool) $properties['isCommonCallable'] ? null : $properties['parameters'], (bool) $properties['isCommonCallable'] ? null : $properties['returnType'], $properties['variadic']);
    }
}
