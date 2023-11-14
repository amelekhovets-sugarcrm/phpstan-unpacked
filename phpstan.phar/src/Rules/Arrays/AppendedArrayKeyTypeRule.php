<?php

declare (strict_types=1);
namespace PHPStan\Rules\Arrays;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\IntegerType;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
/**
 * @deprecated Replaced by PHPStan\Rules\Properties\TypesAssignedToPropertiesRule
 * @implements Rule<Node\Expr\Assign>
 */
class AppendedArrayKeyTypeRule implements Rule
{
    /**
     * @var \PHPStan\Rules\Properties\PropertyReflectionFinder
     */
    private $propertyReflectionFinder;
    /**
     * @var bool
     */
    private $checkUnionTypes;
    public function __construct(PropertyReflectionFinder $propertyReflectionFinder, bool $checkUnionTypes)
    {
        $this->propertyReflectionFinder = $propertyReflectionFinder;
        $this->checkUnionTypes = $checkUnionTypes;
    }
    public function getNodeType() : string
    {
        return Assign::class;
    }
    public function processNode(Node $node, Scope $scope) : array
    {
        if (!$node->var instanceof ArrayDimFetch) {
            return [];
        }
        if (!$node->var->var instanceof Node\Expr\PropertyFetch && !$node->var->var instanceof Node\Expr\StaticPropertyFetch) {
            return [];
        }
        $propertyReflection = $this->propertyReflectionFinder->findPropertyReflectionFromNode($node->var->var, $scope);
        if ($propertyReflection === null) {
            return [];
        }
        $arrayType = $propertyReflection->getReadableType();
        if (!$arrayType->isArray()->yes()) {
            return [];
        }
        if ($node->var->dim !== null) {
            $dimensionType = $scope->getType($node->var->dim);
            $isValidKey = \PHPStan\Rules\Arrays\AllowedArrayKeysTypes::getType()->isSuperTypeOf($dimensionType);
            if (!$isValidKey->yes()) {
                // already handled by InvalidKeyInArrayDimFetchRule
                return [];
            }
            $keyType = $dimensionType->toArrayKey();
            if (!$this->checkUnionTypes && $keyType instanceof UnionType) {
                return [];
            }
        } else {
            $keyType = new IntegerType();
        }
        if (!$arrayType->getIterableKeyType()->isSuperTypeOf($keyType)->yes()) {
            $verbosity = VerbosityLevel::getRecommendedLevelByType($arrayType->getIterableKeyType(), $keyType);
            return [RuleErrorBuilder::message(sprintf('Array (%s) does not accept key %s.', $arrayType->describe($verbosity), $keyType->describe(VerbosityLevel::value())))->build()];
        }
        return [];
    }
}
