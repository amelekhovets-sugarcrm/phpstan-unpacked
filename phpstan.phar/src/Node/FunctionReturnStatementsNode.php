<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Expr\YieldFrom;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\StatementResult;
use PHPStan\Reflection\FunctionReflection;
use function count;
/** @api */
class FunctionReturnStatementsNode extends NodeAbstract implements \PHPStan\Node\ReturnStatementsNode
{
    /**
     * @var \PhpParser\Node\Stmt\Function_
     */
    private $function;
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
     * @var \PHPStan\Reflection\FunctionReflection
     */
    private $functionReflection;
    /**
     * @param list<ReturnStatement> $returnStatements
     * @param list<Yield_|YieldFrom> $yieldStatements
     * @param list<ExecutionEndNode> $executionEnds
     */
    public function __construct(Function_ $function, array $returnStatements, array $yieldStatements, StatementResult $statementResult, array $executionEnds, FunctionReflection $functionReflection)
    {
        $this->function = $function;
        $this->returnStatements = $returnStatements;
        $this->yieldStatements = $yieldStatements;
        $this->statementResult = $statementResult;
        $this->executionEnds = $executionEnds;
        $this->functionReflection = $functionReflection;
        parent::__construct($function->getAttributes());
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
        return $this->function->byRef;
    }
    public function hasNativeReturnTypehint() : bool
    {
        return $this->function->returnType !== null;
    }
    public function getYieldStatements() : array
    {
        return $this->yieldStatements;
    }
    public function isGenerator() : bool
    {
        return count($this->yieldStatements) > 0;
    }
    public function getType() : string
    {
        return 'PHPStan_Node_FunctionReturnStatementsNode';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames() : array
    {
        return [];
    }
    public function getFunctionReflection() : FunctionReflection
    {
        return $this->functionReflection;
    }
}
