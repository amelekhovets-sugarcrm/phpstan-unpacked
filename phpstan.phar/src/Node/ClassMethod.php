<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node\Stmt\ClassMethod as PhpParserClassMethod;
/** @api */
class ClassMethod extends PhpParserClassMethod
{
    /**
     * @var bool
     */
    private $isDeclaredInTrait;
    public function __construct(\PhpParser\Node\Stmt\ClassMethod $node, bool $isDeclaredInTrait)
    {
        $this->isDeclaredInTrait = $isDeclaredInTrait;
        parent::__construct($node->name, ['flags' => $node->flags, 'byRef' => $node->byRef, 'params' => $node->params, 'returnType' => $node->returnType, 'stmts' => $node->stmts, 'attrGroups' => $node->attrGroups], $node->attributes);
    }
    public function getNode() : PhpParserClassMethod
    {
        return $this;
    }
    public function isDeclaredInTrait() : bool
    {
        return $this->isDeclaredInTrait;
    }
}