<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node\Stmt;
/** @api */
class UnreachableStatementNode extends Stmt implements \PHPStan\Node\VirtualNode
{
    /**
     * @var \PhpParser\Node\Stmt
     */
    private $originalStatement;
    public function __construct(Stmt $originalStatement)
    {
        $this->originalStatement = $originalStatement;
        parent::__construct($originalStatement->getAttributes());
    }
    public function getOriginalStatement() : Stmt
    {
        return $this->originalStatement;
    }
    public function getType() : string
    {
        return 'PHPStan_Stmt_UnreachableStatementNode';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames() : array
    {
        return [];
    }
}