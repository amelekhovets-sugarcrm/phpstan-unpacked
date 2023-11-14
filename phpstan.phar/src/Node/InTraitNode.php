<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node;
use PHPStan\Reflection\ClassReflection;
/** @api */
class InTraitNode extends Node\Stmt implements \PHPStan\Node\VirtualNode
{
    /**
     * @var \PhpParser\Node\Stmt\Trait_
     */
    private $originalNode;
    /**
     * @var \PHPStan\Reflection\ClassReflection
     */
    private $traitReflection;
    public function __construct(Node\Stmt\Trait_ $originalNode, ClassReflection $traitReflection)
    {
        $this->originalNode = $originalNode;
        $this->traitReflection = $traitReflection;
        parent::__construct($originalNode->getAttributes());
    }
    public function getOriginalNode() : Node\Stmt\Trait_
    {
        return $this->originalNode;
    }
    public function getTraitReflection() : ClassReflection
    {
        return $this->traitReflection;
    }
    public function getType() : string
    {
        return 'PHPStan_Stmt_InTraitNode';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames() : array
    {
        return [];
    }
}
