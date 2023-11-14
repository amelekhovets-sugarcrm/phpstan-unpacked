<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\Type\DynamicReturnTypeExtensionRegistryProvider;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\ShouldNotHappenException;
use function is_a;
class LazyInternalScopeFactory implements \PHPStan\Analyser\InternalScopeFactory
{
    /**
     * @var class-string
     */
    private $scopeClass;
    /**
     * @var \PHPStan\DependencyInjection\Container
     */
    private $container;
    /**
     * @var bool
     */
    private $explicitMixedInUnknownGenericNew;
    /**
     * @var bool
     */
    private $explicitMixedForGlobalVariables;
    /**
     * @param class-string $scopeClass
     */
    public function __construct(string $scopeClass, Container $container)
    {
        $this->scopeClass = $scopeClass;
        $this->container = $container;
        $this->explicitMixedInUnknownGenericNew = $this->container->getParameter('featureToggles')['explicitMixedInUnknownGenericNew'];
        $this->explicitMixedForGlobalVariables = $this->container->getParameter('featureToggles')['explicitMixedForGlobalVariables'];
    }
    /**
     * @param array<string, ExpressionTypeHolder> $expressionTypes
     * @param array<string, ExpressionTypeHolder> $nativeExpressionTypes
     * @param array<string, ConditionalExpressionHolder[]> $conditionalExpressions
     * @param array<string, true> $currentlyAssignedExpressions
     * @param array<string, true> $currentlyAllowedUndefinedExpressions
     * @param array<(FunctionReflection|MethodReflection)> $inFunctionCallsStack
     *
     * @param \PHPStan\Reflection\FunctionReflection|\PHPStan\Reflection\MethodReflection|null $function
     */
    public function create(\PHPStan\Analyser\ScopeContext $context, bool $declareStrictTypes = \false, $function = null, ?string $namespace = null, array $expressionTypes = [], array $nativeExpressionTypes = [], array $conditionalExpressions = [], array $inClosureBindScopeClasses = [], ?ParametersAcceptor $anonymousFunctionReflection = null, bool $inFirstLevelStatement = \true, array $currentlyAssignedExpressions = [], array $currentlyAllowedUndefinedExpressions = [], array $inFunctionCallsStack = [], bool $afterExtractCall = \false, ?\PHPStan\Analyser\Scope $parentScope = null, bool $nativeTypesPromoted = \false) : \PHPStan\Analyser\MutatingScope
    {
        $scopeClass = $this->scopeClass;
        if (!is_a($scopeClass, \PHPStan\Analyser\MutatingScope::class, \true)) {
            throw new ShouldNotHappenException();
        }
        return new $scopeClass($this, $this->container->getByType(ReflectionProvider::class), $this->container->getByType(InitializerExprTypeResolver::class), $this->container->getByType(DynamicReturnTypeExtensionRegistryProvider::class)->getRegistry(), $this->container->getByType(ExprPrinter::class), $this->container->getByType(\PHPStan\Analyser\TypeSpecifier::class), $this->container->getByType(PropertyReflectionFinder::class), $this->container->getService('currentPhpVersionSimpleParser'), $this->container->getByType(\PHPStan\Analyser\NodeScopeResolver::class), $this->container->getByType(\PHPStan\Analyser\ConstantResolver::class), $context, $this->container->getByType(PhpVersion::class), $declareStrictTypes, $function, $namespace, $expressionTypes, $nativeExpressionTypes, $conditionalExpressions, $inClosureBindScopeClasses, $anonymousFunctionReflection, $inFirstLevelStatement, $currentlyAssignedExpressions, $currentlyAllowedUndefinedExpressions, $inFunctionCallsStack, $afterExtractCall, $parentScope, $nativeTypesPromoted, $this->explicitMixedInUnknownGenericNew, $this->explicitMixedForGlobalVariables);
    }
}
