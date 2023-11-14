<?php

declare (strict_types=1);
namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Comparison\ImpossibleCheckTypeHelper;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use function count;
use function in_array;
class TypeSpecifyingFunctionsDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension, TypeSpecifierAwareExtension
{
    /**
     * @var \PHPStan\Reflection\ReflectionProvider
     */
    private $reflectionProvider;
    /**
     * @var bool
     */
    private $treatPhpDocTypesAsCertain;
    /**
     * @var string[]
     */
    private $universalObjectCratesClasses;
    /**
     * @var bool
     */
    private $nullContextForVoidReturningFunctions;
    /**
     * @var \PHPStan\Analyser\TypeSpecifier
     */
    private $typeSpecifier;
    /**
     * @var \PHPStan\Rules\Comparison\ImpossibleCheckTypeHelper|null
     */
    private $helper;
    /**
     * @param string[] $universalObjectCratesClasses
     */
    public function __construct(ReflectionProvider $reflectionProvider, bool $treatPhpDocTypesAsCertain, array $universalObjectCratesClasses, bool $nullContextForVoidReturningFunctions)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
        $this->universalObjectCratesClasses = $universalObjectCratesClasses;
        $this->nullContextForVoidReturningFunctions = $nullContextForVoidReturningFunctions;
    }
    public function setTypeSpecifier(TypeSpecifier $typeSpecifier) : void
    {
        $this->typeSpecifier = $typeSpecifier;
    }
    public function isFunctionSupported(FunctionReflection $functionReflection) : bool
    {
        return in_array($functionReflection->getName(), ['array_key_exists', 'key_exists', 'in_array', 'is_subclass_of'], \true);
    }
    public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope) : Type
    {
        if (count($functionCall->getArgs()) === 0) {
            return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
        }
        $isAlways = $this->getHelper()->findSpecifiedType($scope, $functionCall);
        if ($isAlways === null) {
            return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
        }
        return new ConstantBooleanType($isAlways);
    }
    private function getHelper() : ImpossibleCheckTypeHelper
    {
        if ($this->helper === null) {
            $this->helper = new ImpossibleCheckTypeHelper($this->reflectionProvider, $this->typeSpecifier, $this->universalObjectCratesClasses, $this->treatPhpDocTypesAsCertain, $this->nullContextForVoidReturningFunctions);
        }
        return $this->helper;
    }
}
