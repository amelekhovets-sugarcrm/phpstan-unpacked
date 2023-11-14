<?php

declare (strict_types=1);
namespace PHPStan\Node\Expr;

use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;
class GetOffsetValueTypeExpr extends Expr implements VirtualNode
{
    /**
     * @var \PhpParser\Node\Expr
     */
    private $var;
    /**
     * @var \PhpParser\Node\Expr
     */
    private $dim;
    public function __construct(Expr $var, Expr $dim)
    {
        $this->var = $var;
        $this->dim = $dim;
        parent::__construct([]);
    }
    public function getVar() : Expr
    {
        return $this->var;
    }
    public function getDim() : Expr
    {
        return $this->dim;
    }
    public function getType() : string
    {
        return 'PHPStan_Node_GetOffsetValueTypeExpr';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames() : array
    {
        return [];
    }
}
