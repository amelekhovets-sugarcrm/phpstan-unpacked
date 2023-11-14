<?php

declare (strict_types=1);
namespace PHPStan\Rules\Arrays;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InForeachNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
/**
 * @implements Rule<InForeachNode>
 */
class IterableInForeachRule implements Rule
{
    /**
     * @var \PHPStan\Rules\RuleLevelHelper
     */
    private $ruleLevelHelper;
    public function __construct(RuleLevelHelper $ruleLevelHelper)
    {
        $this->ruleLevelHelper = $ruleLevelHelper;
    }
    public function getNodeType() : string
    {
        return InForeachNode::class;
    }
    public function processNode(Node $node, Scope $scope) : array
    {
        $originalNode = $node->getOriginalNode();
        $typeResult = $this->ruleLevelHelper->findTypeToCheck($scope, $originalNode->expr, 'Iterating over an object of an unknown class %s.', static function (Type $type) : bool {
            return $type->isIterable()->yes();
        });
        $type = $typeResult->getType();
        if ($type instanceof ErrorType) {
            return $typeResult->getUnknownClassErrors();
        }
        if ($type->isIterable()->yes()) {
            return [];
        }
        return [RuleErrorBuilder::message(sprintf('Argument of an invalid type %s supplied for foreach, only iterables are supported.', $type->describe(VerbosityLevel::typeOnly())))->line($originalNode->expr->getLine())->build()];
    }
}