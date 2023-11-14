<?php

declare (strict_types=1);
namespace PHPStan\Node\Constant;

use PhpParser\Node\Expr\ClassConstFetch;
use PHPStan\Analyser\Scope;
/** @api */
class ClassConstantFetch
{
    /**
     * @var \PhpParser\Node\Expr\ClassConstFetch
     */
    private $node;
    /**
     * @var \PHPStan\Analyser\Scope
     */
    private $scope;
    public function __construct(ClassConstFetch $node, Scope $scope)
    {
        $this->node = $node;
        $this->scope = $scope;
    }
    public function getNode() : ClassConstFetch
    {
        return $this->node;
    }
    public function getScope() : Scope
    {
        return $this->scope;
    }
}
