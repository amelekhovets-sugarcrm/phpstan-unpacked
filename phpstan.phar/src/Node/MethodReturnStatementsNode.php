<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Expr\YieldFrom;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\StatementResult;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use function count;
/** @api */
class MethodReturnStatementsNode extends NodeAbstract implements \PHPStan\Node\ReturnStatementsNode
{
    /**
     * @var list<ReturnStatement>
     */
    private $returnStatements;
    /**
     * @var list<(Yield_ | YieldFrom)>
     */
    private $yieldStatements;
    /**
     * @var \PHPStan\Analyser\StatementResult
     */
    private $statementResult;
    /**
     * @var list<ExecutionEndNode>
     */
    private $executionEnds;
    /**
     * @var \PHPStan\Reflection\ClassReflection
     */
    private $classReflection;
    /**
     * @var \PHPStan\Reflection\MethodReflection
     */
    private $methodReflection;
    /**
     * @var \PhpParser\Node\Stmt\ClassMethod
     */
    private $classMethod;
    /**
     * @param list<ReturnStatement> $returnStatements
     * @param list<Yield_|YieldFrom> $yieldStatements
     * @param list<ExecutionEndNode> $executionEnds
     */
    public function __construct(ClassMethod $method, array $returnStatements, array $yieldStatements, StatementResult $statementResult, array $executionEnds, ClassReflection $classReflection, MethodReflection $methodReflection)
    {
        $this->returnStatements = $returnStatements;
        $this->yieldStatements = $yieldStatements;
        $this->statementResult = $statementResult;
        $this->executionEnds = $executionEnds;
        $this->classReflection = $classReflection;
        $this->methodReflection = $methodReflection;
        parent::__construct($method->getAttributes());
        $this->classMethod = $method;
    }
    public function getReturnStatements() : array
    {
        return $this->returnStatements;
    }
    public function getStatementResult() : StatementResult
    {
        return $this->statementResult;
    }
    public function getExecutionEnds() : array
    {
        return $this->executionEnds;
    }
    public function returnsByRef() : bool
    {
        return $this->classMethod->byRef;
    }
    public function hasNativeReturnTypehint() : bool
    {
        return $this->classMethod->returnType !== null;
    }
    public function getMethodName() : string
    {
        return $this->classMethod->name->toString();
    }
    public function getYieldStatements() : array
    {
        return $this->yieldStatements;
    }
    public function getClassReflection() : ClassReflection
    {
        return $this->classReflection;
    }
    public function getMethodReflection() : MethodReflection
    {
        return $this->methodReflection;
    }
    public function isGenerator() : bool
    {
        return count($this->yieldStatements) > 0;
    }
    public function getType() : string
    {
        return 'PHPStan_Node_MethodReturnStatementsNode';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames() : array
    {
        return [];
    }
}
