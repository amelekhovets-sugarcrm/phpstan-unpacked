<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
/** @api */
class InClassMethodNode extends Node\Stmt implements \PHPStan\Node\VirtualNode
{
    /**
     * @var \PHPStan\Reflection\ClassReflection
     */
    private $classReflection;
    /**
     * @var \PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection
     */
    private $methodReflection;
    /**
     * @var \PhpParser\Node\Stmt\ClassMethod
     */
    private $originalNode;
    public function __construct(ClassReflection $classReflection, PhpMethodFromParserNodeReflection $methodReflection, Node\Stmt\ClassMethod $originalNode)
    {
        $this->classReflection = $classReflection;
        $this->methodReflection = $methodReflection;
        $this->originalNode = $originalNode;
        parent::__construct($originalNode->getAttributes());
    }
    public function getClassReflection() : ClassReflection
    {
        return $this->classReflection;
    }
    public function getMethodReflection() : PhpMethodFromParserNodeReflection
    {
        return $this->methodReflection;
    }
    public function getOriginalNode() : Node\Stmt\ClassMethod
    {
        return $this->originalNode;
    }
    public function getType() : string
    {
        return 'PHPStan_Stmt_InClassMethodNode';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames() : array
    {
        return [];
    }
}