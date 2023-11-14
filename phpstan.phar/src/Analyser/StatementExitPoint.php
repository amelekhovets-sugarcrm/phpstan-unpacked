<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Node\Stmt;
/** @api */
class StatementExitPoint
{
    /**
     * @var \PhpParser\Node\Stmt
     */
    private $statement;
    /**
     * @var \PHPStan\Analyser\MutatingScope
     */
    private $scope;
    public function __construct(Stmt $statement, \PHPStan\Analyser\MutatingScope $scope)
    {
        $this->statement = $statement;
        $this->scope = $scope;
    }
    public function getStatement() : Stmt
    {
        return $this->statement;
    }
    public function getScope() : \PHPStan\Analyser\MutatingScope
    {
        return $this->scope;
    }
}
