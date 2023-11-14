<?php

declare (strict_types=1);
namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
/**
 * @implements Rule<InClassNode>
 */
class LocalTypeAliasesRule implements Rule
{
    /**
     * @var \PHPStan\Rules\Classes\LocalTypeAliasesCheck
     */
    private $check;
    public function __construct(\PHPStan\Rules\Classes\LocalTypeAliasesCheck $check)
    {
        $this->check = $check;
    }
    public function getNodeType() : string
    {
        return InClassNode::class;
    }
    public function processNode(Node $node, Scope $scope) : array
    {
        return $this->check->check($node->getClassReflection());
    }
}