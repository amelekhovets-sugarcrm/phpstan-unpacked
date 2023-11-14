<?php

declare (strict_types=1);
namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\VarTagChangedExpressionTypeNode;
use PHPStan\Rules\Rule;
/**
 * @implements Rule<VarTagChangedExpressionTypeNode>
 */
class VarTagChangedExpressionTypeRule implements Rule
{
    /**
     * @var \PHPStan\Rules\PhpDoc\VarTagTypeRuleHelper
     */
    private $varTagTypeRuleHelper;
    public function __construct(\PHPStan\Rules\PhpDoc\VarTagTypeRuleHelper $varTagTypeRuleHelper)
    {
        $this->varTagTypeRuleHelper = $varTagTypeRuleHelper;
    }
    public function getNodeType() : string
    {
        return VarTagChangedExpressionTypeNode::class;
    }
    public function processNode(Node $node, Scope $scope) : array
    {
        return $this->varTagTypeRuleHelper->checkExprType($scope, $node->getExpr(), $node->getVarTag()->getType());
    }
}
