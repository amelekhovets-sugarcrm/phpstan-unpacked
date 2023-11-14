<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\DependencyInjection\Type\DynamicReturnTypeExtensionRegistryProvider;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Parser\Parser;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\ShouldNotHappenException;
use function is_a;
class DirectInternalScopeFactory implements \PHPStan\Analyser\InternalScopeFactory
{
    /**
     * @var class-string
     */
    private $scopeClass;
    /**
     * @var \PHPStan\Reflection\ReflectionProvider
     */
    private $reflectionProvider;
    /**
     * @var \PHPStan\Reflection\InitializerExprTypeResolver
     */
    private $initializerExprTypeResolver;
    /**
     * @var \PHPStan\DependencyInjection\Type\DynamicReturnTypeExtensionRegistryProvider
     */
    private $dynamicReturnTypeExtensionRegistryProvider;
    /**
     * @var \PHPStan\Node\Printer\ExprPrinter
     */
    private $exprPrinter;
    /**
     * @var \PHPStan\Analyser\TypeSpecifier
     */
    private $typeSpecifier;
    /**
     * @var \PHPStan\Rules\Properties\PropertyReflectionFinder
     */
    private $propertyReflectionFinder;
    /**
     * @var \PHPStan\Parser\Parser
     */
    private $parser;
    /**
     * @var \PHPStan\Analyser\NodeScopeResolver
     */
    private $nodeScopeResolver;
    /**
     * @var \PHPStan\Php\PhpVersion
     */
    private $phpVersion;
    /**
     * @var bool
     */
    private $explicitMixedInUnknownGenericNew;
    /**
     * @var bool
     */
    private $explicitMixedForGlobalVariables;
    /**
     * @var \PHPStan\Analyser\ConstantResolver
     */
    private $constantResolver;
    /**
     * @param class-string $scopeClass
     */
    public function __construct(string $scopeClass, ReflectionProvider $reflectionProvider, InitializerExprTypeResolver $initializerExprTypeResolver, DynamicReturnTypeExtensionRegistryProvider $dynamicReturnTypeExtensionRegistryProvider, ExprPrinter $exprPrinter, \PHPStan\Analyser\TypeSpecifier $typeSpecifier, PropertyReflectionFinder $propertyReflectionFinder, Parser $parser, \PHPStan\Analyser\NodeScopeResolver $nodeScopeResolver, PhpVersion $phpVersion, bool $explicitMixedInUnknownGenericNew, bool $explicitMixedForGlobalVariables, \PHPStan\Analyser\ConstantResolver $constantResolver)
    {
        $this->scopeClass = $scopeClass;
        $this->reflectionProvider = $reflectionProvider;
        $this->initializerExprTypeResolver = $initializerExprTypeResolver;
        $this->dynamicReturnTypeExtensionRegistryProvider = $dynamicReturnTypeExtensionRegistryProvider;
        $this->exprPrinter = $exprPrinter;
        $this->typeSpecifier = $typeSpecifier;
        $this->propertyReflectionFinder = $propertyReflectionFinder;
        $this->parser = $parser;
        $this->nodeScopeResolver = $nodeScopeResolver;
        $this->phpVersion = $phpVersion;
        $this->explicitMixedInUnknownGenericNew = $explicitMixedInUnknownGenericNew;
        $this->explicitMixedForGlobalVariables = $explicitMixedForGlobalVariables;
        $this->constantResolver = $constantResolver;
    }
    /**
     * @param array<string, ExpressionTypeHolder> $expressionTypes
     * @param array<string, ExpressionTypeHolder> $nativeExpressionTypes
     * @param array<string, ConditionalExpressionHolder[]> $conditionalExpressions
     * @param array<(FunctionReflection|MethodReflection)> $inFunctionCallsStack
     * @param array<string, true> $currentlyAssignedExpressions
     * @param array<string, true> $currentlyAllowedUndefinedExpressions
     * @param \PHPStan\Reflection\FunctionReflection|\PHPStan\Reflection\MethodReflection|null $function
     */
    public function create(\PHPStan\Analyser\ScopeContext $context, bool $declareStrictTypes = \false, $function = null, ?string $namespace = null, array $expressionTypes = [], array $nativeExpressionTypes = [], array $conditionalExpressions = [], array $inClosureBindScopeClasses = [], ?ParametersAcceptor $anonymousFunctionReflection = null, bool $inFirstLevelStatement = \true, array $currentlyAssignedExpressions = [], array $currentlyAllowedUndefinedExpressions = [], array $inFunctionCallsStack = [], bool $afterExtractCall = \false, ?\PHPStan\Analyser\Scope $parentScope = null, bool $nativeTypesPromoted = \false) : \PHPStan\Analyser\MutatingScope
    {
        $scopeClass = $this->scopeClass;
        if (!is_a($scopeClass, \PHPStan\Analyser\MutatingScope::class, \true)) {
            throw new ShouldNotHappenException();
        }
        return new $scopeClass($this, $this->reflectionProvider, $this->initializerExprTypeResolver, $this->dynamicReturnTypeExtensionRegistryProvider->getRegistry(), $this->exprPrinter, $this->typeSpecifier, $this->propertyReflectionFinder, $this->parser, $this->nodeScopeResolver, $this->constantResolver, $context, $this->phpVersion, $declareStrictTypes, $function, $namespace, $expressionTypes, $nativeExpressionTypes, $conditionalExpressions, $inClosureBindScopeClasses, $anonymousFunctionReflection, $inFirstLevelStatement, $currentlyAssignedExpressions, $currentlyAllowedUndefinedExpressions, $inFunctionCallsStack, $afterExtractCall, $parentScope, $nativeTypesPromoted, $this->explicitMixedInUnknownGenericNew, $this->explicitMixedForGlobalVariables);
    }
}
