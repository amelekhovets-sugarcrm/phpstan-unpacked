<?php

declare (strict_types=1);
namespace PHPStan\Node\Expr;

use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;
class GetIterableValueTypeExpr extends Expr implements VirtualNode
{
    /**
     * @var \PhpParser\Node\Expr
     */
    private $expr;
    public function __construct(Expr $expr)
    {
        $this->expr = $expr;
        parent::__construct([]);
    }
    public function getExpr() : Expr
    {
        return $this->expr;
    }
    public function getType() : string
    {
        return 'PHPStan_Node_GetIterableValueTypeExpr';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames() : array
    {
        return [];
    }
}
