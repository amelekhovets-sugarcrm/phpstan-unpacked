<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;
class PropertyAssignNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    /**
     * @var \PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch
     */
    private $propertyFetch;
    /**
     * @var \PhpParser\Node\Expr
     */
    private $assignedExpr;
    /**
     * @var bool
     */
    private $assignOp;
    /**
     * @param \PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch $propertyFetch
     */
    public function __construct($propertyFetch, Expr $assignedExpr, bool $assignOp)
    {
        $this->propertyFetch = $propertyFetch;
        $this->assignedExpr = $assignedExpr;
        $this->assignOp = $assignOp;
        parent::__construct($propertyFetch->getAttributes());
    }
    /**
     * @return \PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch
     */
    public function getPropertyFetch()
    {
        return $this->propertyFetch;
    }
    public function getAssignedExpr() : Expr
    {
        return $this->assignedExpr;
    }
    public function isAssignOp() : bool
    {
        return $this->assignOp;
    }
    public function getType() : string
    {
        return 'PHPStan_Node_PropertyAssignNodeNode';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames() : array
    {
        return [];
    }
}
