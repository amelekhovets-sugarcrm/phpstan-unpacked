<?php

declare (strict_types=1);
namespace PHPStan\Rules\Arrays;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
/**
 * @implements Rule<Node\Expr\ArrayDimFetch>
 */
class InvalidKeyInArrayDimFetchRule implements Rule
{
    /**
     * @var \PHPStan\Rules\RuleLevelHelper
     */
    private $ruleLevelHelper;
    /**
     * @var bool
     */
    private $reportMaybes;
    public function __construct(RuleLevelHelper $ruleLevelHelper, bool $reportMaybes)
    {
        $this->ruleLevelHelper = $ruleLevelHelper;
        $this->reportMaybes = $reportMaybes;
    }
    public function getNodeType() : string
    {
        return Node\Expr\ArrayDimFetch::class;
    }
    public function processNode(Node $node, Scope $scope) : array
    {
        if ($node->dim === null) {
            return [];
        }
        $dimensionType = $scope->getType($node->dim);
        if ($dimensionType instanceof MixedType) {
            return [];
        }
        $varType = $this->ruleLevelHelper->findTypeToCheck($scope, $node->var, '', static function (Type $varType) use($dimensionType) : bool {
            return $varType->isArray()->no() || \PHPStan\Rules\Arrays\AllowedArrayKeysTypes::getType()->isSuperTypeOf($dimensionType)->yes();
        })->getType();
        if ($varType instanceof ErrorType || $varType->isArray()->no()) {
            return [];
        }
        $isSuperType = \PHPStan\Rules\Arrays\AllowedArrayKeysTypes::getType()->isSuperTypeOf($dimensionType);
        if ($isSuperType->yes() || $isSuperType->maybe() && !$this->reportMaybes) {
            return [];
        }
        return [RuleErrorBuilder::message(sprintf('%s array key type %s.', $isSuperType->no() ? 'Invalid' : 'Possibly invalid', $dimensionType->describe(VerbosityLevel::typeOnly())))->build()];
    }
}
