<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;
use PHPStan\PhpDoc\Tag\VarTag;
class VarTagChangedExpressionTypeNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    /**
     * @var \PHPStan\PhpDoc\Tag\VarTag
     */
    private $varTag;
    /**
     * @var \PhpParser\Node\Expr
     */
    private $expr;
    public function __construct(VarTag $varTag, Expr $expr)
    {
        $this->varTag = $varTag;
        $this->expr = $expr;
        parent::__construct($expr->getAttributes());
    }
    public function getVarTag() : VarTag
    {
        return $this->varTag;
    }
    public function getExpr() : Expr
    {
        return $this->expr;
    }
    public function getType() : string
    {
        return 'PHPStan_Node_VarTagChangedExpressionType';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames() : array
    {
        return [];
    }
}
