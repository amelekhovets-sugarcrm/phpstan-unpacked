<?php

declare (strict_types=1);
namespace PHPStan\Rules;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\CallableType;
use PHPStan\Type\ClosureType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StaticType;
use PHPStan\Type\StrictMixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function count;
use function sprintf;
use function strpos;
class RuleLevelHelper
{
    /**
     * @var \PHPStan\Reflection\ReflectionProvider
     */
    private $reflectionProvider;
    /**
     * @var bool
     */
    private $checkNullables;
    /**
     * @var bool
     */
    private $checkThisOnly;
    /**
     * @var bool
     */
    private $checkUnionTypes;
    /**
     * @var bool
     */
    private $checkExplicitMixed;
    /**
     * @var bool
     */
    private $checkImplicitMixed;
    /**
     * @var bool
     */
    private $newRuleLevelHelper;
    /**
     * @var bool
     */
    private $checkBenevolentUnionTypes;
    public function __construct(ReflectionProvider $reflectionProvider, bool $checkNullables, bool $checkThisOnly, bool $checkUnionTypes, bool $checkExplicitMixed, bool $checkImplicitMixed, bool $newRuleLevelHelper, bool $checkBenevolentUnionTypes)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->checkNullables = $checkNullables;
        $this->checkThisOnly = $checkThisOnly;
        $this->checkUnionTypes = $checkUnionTypes;
        $this->checkExplicitMixed = $checkExplicitMixed;
        $this->checkImplicitMixed = $checkImplicitMixed;
        $this->newRuleLevelHelper = $newRuleLevelHelper;
        $this->checkBenevolentUnionTypes = $checkBenevolentUnionTypes;
    }
    /** @api */
    public function isThis(Expr $expression) : bool
    {
        return $expression instanceof Expr\Variable && $expression->name === 'this';
    }
    /** @api */
    public function accepts(Type $acceptingType, Type $acceptedType, bool $strictTypes) : bool
    {
        return $this->acceptsWithReason($acceptingType, $acceptedType, $strictTypes)->result;
    }
    private function transformCommonType(Type $type) : Type
    {
        if (!$this->checkExplicitMixed && !$this->checkImplicitMixed) {
            return $type;
        }
        return TypeTraverser::map($type, function (Type $type, callable $traverse) {
            if ($type instanceof TemplateMixedType) {
                return $type->toStrictMixedType();
            }
            if ($type instanceof MixedType && ($type->isExplicitMixed() && $this->checkExplicitMixed || !$type->isExplicitMixed() && $this->checkImplicitMixed)) {
                return new StrictMixedType();
            }
            return $traverse($type);
        });
    }
    /**
     * @return array{Type, bool}
     */
    private function transformAcceptedType(Type $acceptingType, Type $acceptedType) : array
    {
        $checkForUnion = $this->checkUnionTypes;
        $acceptedType = TypeTraverser::map($acceptedType, function (Type $acceptedType, callable $traverse) use($acceptingType, &$checkForUnion) : Type {
            if ($acceptedType instanceof CallableType) {
                if ($acceptedType->isCommonCallable()) {
                    return new CallableType(null, null, $acceptedType->isVariadic());
                }
                return new CallableType($acceptedType->getParameters(), $traverse($this->transformCommonType($acceptedType->getReturnType())), $acceptedType->isVariadic());
            }
            if ($acceptedType instanceof ClosureType) {
                return new ClosureType($acceptedType->getParameters(), $traverse($this->transformCommonType($acceptedType->getReturnType())), $acceptedType->isVariadic(), $acceptedType->getTemplateTypeMap(), $acceptedType->getResolvedTemplateTypeMap(), $acceptedType->getCallSiteVarianceMap());
            }
            if (!$this->checkNullables && !$acceptingType instanceof NullType && !$acceptedType instanceof NullType && !$acceptedType instanceof BenevolentUnionType) {
                return $traverse(TypeCombinator::removeNull($acceptedType));
            }
            if ($this->checkBenevolentUnionTypes) {
                if ($acceptedType instanceof BenevolentUnionType) {
                    $checkForUnion = \true;
                    return $traverse(TypeUtils::toStrictUnion($acceptedType));
                }
            }
            return $traverse($this->transformCommonType($acceptedType));
        });
        return [$acceptedType, $checkForUnion];
    }
    public function acceptsWithReason(Type $acceptingType, Type $acceptedType, bool $strictTypes) : \PHPStan\Rules\RuleLevelHelperAcceptsResult
    {
        if ($this->newRuleLevelHelper) {
            [$acceptedType, $checkForUnion] = $this->transformAcceptedType($acceptingType, $acceptedType);
            $acceptingType = $this->transformCommonType($acceptingType);
            $accepts = $acceptingType->acceptsWithReason($acceptedType, $strictTypes);
            return new \PHPStan\Rules\RuleLevelHelperAcceptsResult($checkForUnion ? $accepts->yes() : !$accepts->no(), $accepts->reasons);
        }
        $checkForUnion = $this->checkUnionTypes;
        if ($this->checkBenevolentUnionTypes) {
            $traverse = static function (Type $type, callable $traverse) use(&$checkForUnion) : Type {
                if ($type instanceof BenevolentUnionType) {
                    $checkForUnion = \true;
                    return TypeUtils::toStrictUnion($type);
                }
                return $traverse($type);
            };
            $acceptedType = TypeTraverser::map($acceptedType, $traverse);
        }
        if ($this->checkExplicitMixed) {
            $traverse = static function (Type $type, callable $traverse) : Type {
                if ($type instanceof TemplateMixedType) {
                    return $type->toStrictMixedType();
                }
                if ($type instanceof MixedType && $type->isExplicitMixed()) {
                    return new StrictMixedType();
                }
                return $traverse($type);
            };
            $acceptingType = TypeTraverser::map($acceptingType, $traverse);
            $acceptedType = TypeTraverser::map($acceptedType, $traverse);
        }
        if ($this->checkImplicitMixed) {
            $traverse = static function (Type $type, callable $traverse) : Type {
                if ($type instanceof TemplateMixedType) {
                    return $type->toStrictMixedType();
                }
                if ($type instanceof MixedType && !$type->isExplicitMixed()) {
                    return new StrictMixedType();
                }
                return $traverse($type);
            };
            $acceptingType = TypeTraverser::map($acceptingType, $traverse);
            $acceptedType = TypeTraverser::map($acceptedType, $traverse);
        }
        if (!$this->checkNullables && !$acceptingType instanceof NullType && !$acceptedType instanceof NullType && !$acceptedType instanceof BenevolentUnionType) {
            $acceptedType = TypeCombinator::removeNull($acceptedType);
        }
        $accepts = $acceptingType->acceptsWithReason($acceptedType, $strictTypes);
        if ($accepts->yes()) {
            return new \PHPStan\Rules\RuleLevelHelperAcceptsResult(\true, $accepts->reasons);
        }
        if ($acceptingType instanceof UnionType) {
            $reasons = [];
            foreach ($acceptingType->getTypes() as $innerType) {
                $accepts = self::acceptsWithReason($innerType, $acceptedType, $strictTypes);
                if ($accepts->result) {
                    return $accepts;
                }
                $reasons = array_merge($reasons, $accepts->reasons);
            }
            return new \PHPStan\Rules\RuleLevelHelperAcceptsResult(\false, $reasons);
        }
        if ($acceptedType->isArray()->yes() && $acceptingType->isArray()->yes() && ($acceptedType->isConstantArray()->no() || !$acceptedType->isIterableAtLeastOnce()->no()) && $acceptingType->isConstantArray()->no()) {
            if ($acceptingType->isIterableAtLeastOnce()->yes() && !$acceptedType->isIterableAtLeastOnce()->yes()) {
                $verbosity = VerbosityLevel::getRecommendedLevelByType($acceptingType, $acceptedType);
                return new \PHPStan\Rules\RuleLevelHelperAcceptsResult(\false, [sprintf('%s %s empty.', $acceptedType->describe($verbosity), $acceptedType->isIterableAtLeastOnce()->no() ? 'is' : 'might be')]);
            }
            if ($acceptingType->isList()->yes() && !$acceptedType->isList()->yes()) {
                $report = $checkForUnion || $acceptedType->isList()->no();
                if ($report) {
                    $verbosity = VerbosityLevel::getRecommendedLevelByType($acceptingType, $acceptedType);
                    return new \PHPStan\Rules\RuleLevelHelperAcceptsResult(\false, [sprintf('%s %s a list.', $acceptedType->describe($verbosity), $acceptedType->isList()->no() ? 'is not' : 'might not be')]);
                }
            }
            return self::acceptsWithReason($acceptingType->getIterableKeyType(), $acceptedType->getIterableKeyType(), $strictTypes)->and(self::acceptsWithReason($acceptingType->getIterableValueType(), $acceptedType->getIterableValueType(), $strictTypes));
        }
        return new \PHPStan\Rules\RuleLevelHelperAcceptsResult($checkForUnion ? $accepts->yes() : !$accepts->no(), $accepts->reasons);
    }
    /**
     * @api
     * @param callable(Type $type): bool $unionTypeCriteriaCallback
     */
    public function findTypeToCheck(Scope $scope, Expr $var, string $unknownClassErrorPattern, callable $unionTypeCriteriaCallback) : \PHPStan\Rules\FoundTypeResult
    {
        if ($this->checkThisOnly && !$this->isThis($var)) {
            return new \PHPStan\Rules\FoundTypeResult(new ErrorType(), [], [], null);
        }
        $type = $scope->getType($var);
        if (!$this->checkNullables && !$type->isNull()->yes()) {
            $type = TypeCombinator::removeNull($type);
        }
        if ($this->checkExplicitMixed && $type instanceof MixedType && !$type instanceof TemplateMixedType && $type->isExplicitMixed()) {
            return new \PHPStan\Rules\FoundTypeResult(new StrictMixedType(), [], [], null);
        }
        if ($this->checkImplicitMixed && $type instanceof MixedType && !$type instanceof TemplateMixedType && !$type->isExplicitMixed()) {
            return new \PHPStan\Rules\FoundTypeResult(new StrictMixedType(), [], [], null);
        }
        if ($type instanceof MixedType || $type instanceof NeverType) {
            return new \PHPStan\Rules\FoundTypeResult(new ErrorType(), [], [], null);
        }
        if ($type instanceof StaticType) {
            $type = $type->getStaticObjectType();
        }
        $errors = [];
        $directClassNames = $type->getObjectClassNames();
        $hasClassExistsClass = \false;
        foreach ($directClassNames as $referencedClass) {
            if ($this->reflectionProvider->hasClass($referencedClass)) {
                $classReflection = $this->reflectionProvider->getClass($referencedClass);
                if (!$classReflection->isTrait()) {
                    continue;
                }
            }
            if ($scope->isInClassExists($referencedClass)) {
                $hasClassExistsClass = \true;
                continue;
            }
            $errors[] = \PHPStan\Rules\RuleErrorBuilder::message(sprintf($unknownClassErrorPattern, $referencedClass))->line($var->getLine())->discoveringSymbolsTip()->build();
        }
        if (count($errors) > 0 || $hasClassExistsClass) {
            return new \PHPStan\Rules\FoundTypeResult(new ErrorType(), [], $errors, null);
        }
        if (!$this->checkUnionTypes && $type instanceof ObjectWithoutClassType) {
            return new \PHPStan\Rules\FoundTypeResult(new ErrorType(), [], [], null);
        }
        if (!$this->checkUnionTypes && $type instanceof UnionType && !$type instanceof BenevolentUnionType || !$this->checkBenevolentUnionTypes && $type instanceof BenevolentUnionType) {
            $newTypes = [];
            foreach ($type->getTypes() as $innerType) {
                if (!$unionTypeCriteriaCallback($innerType)) {
                    continue;
                }
                $newTypes[] = $innerType;
            }
            if (count($newTypes) > 0) {
                return new \PHPStan\Rules\FoundTypeResult(TypeCombinator::union(...$newTypes), $directClassNames, [], null);
            }
        }
        $tip = null;
        if (strpos($type->describe(VerbosityLevel::typeOnly()), 'PhpParser\\Node\\Arg|PhpParser\\Node\\VariadicPlaceholder') !== \false && !$unionTypeCriteriaCallback($type)) {
            $tip = 'Use <fg=cyan>->getArgs()</> instead of <fg=cyan>->args</>.';
        }
        return new \PHPStan\Rules\FoundTypeResult($type, $directClassNames, [], $tip);
    }
}
