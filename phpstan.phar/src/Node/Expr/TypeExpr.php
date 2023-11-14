<?php

declare (strict_types=1);
namespace PHPStan\Node\Expr;

use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;
use PHPStan\Type\Type;
class TypeExpr extends Expr implements VirtualNode
{
    /**
     * @var \PHPStan\Type\Type
     */
    private $exprType;
    public function __construct(Type $exprType)
    {
        $this->exprType = $exprType;
        parent::__construct();
    }
    public function getExprType() : Type
    {
        return $this->exprType;
    }
    public function getType() : string
    {
        return 'PHPStan_Node_TypeExpr';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames() : array
    {
        return [];
    }
}
