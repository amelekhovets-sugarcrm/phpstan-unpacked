<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

class ExpressionResult
{
    /**
     * @var \PHPStan\Analyser\MutatingScope
     */
    private $scope;
    /**
     * @var bool
     */
    private $hasYield;
    /**
     * @var ThrowPoint[]
     */
    private $throwPoints;
    /** @var (callable(): MutatingScope)|null */
    private $truthyScopeCallback;
    /**
     * @var \PHPStan\Analyser\MutatingScope|null
     */
    private $truthyScope;
    /** @var (callable(): MutatingScope)|null */
    private $falseyScopeCallback;
    /**
     * @var \PHPStan\Analyser\MutatingScope|null
     */
    private $falseyScope;
    /**
     * @param ThrowPoint[] $throwPoints
     * @param (callable(): MutatingScope)|null $truthyScopeCallback
     * @param (callable(): MutatingScope)|null $falseyScopeCallback
     */
    public function __construct(\PHPStan\Analyser\MutatingScope $scope, bool $hasYield, array $throwPoints, ?callable $truthyScopeCallback = null, ?callable $falseyScopeCallback = null)
    {
        $this->scope = $scope;
        $this->hasYield = $hasYield;
        $this->throwPoints = $throwPoints;
        $this->truthyScopeCallback = $truthyScopeCallback;
        $this->falseyScopeCallback = $falseyScopeCallback;
    }
    public function getScope() : \PHPStan\Analyser\MutatingScope
    {
        return $this->scope;
    }
    public function hasYield() : bool
    {
        return $this->hasYield;
    }
    /**
     * @return ThrowPoint[]
     */
    public function getThrowPoints() : array
    {
        return $this->throwPoints;
    }
    public function getTruthyScope() : \PHPStan\Analyser\MutatingScope
    {
        if ($this->truthyScopeCallback === null) {
            return $this->scope;
        }
        if ($this->truthyScope !== null) {
            return $this->truthyScope;
        }
        $callback = $this->truthyScopeCallback;
        $this->truthyScope = $callback();
        return $this->truthyScope;
    }
    public function getFalseyScope() : \PHPStan\Analyser\MutatingScope
    {
        if ($this->falseyScopeCallback === null) {
            return $this->scope;
        }
        if ($this->falseyScope !== null) {
            return $this->falseyScope;
        }
        $callback = $this->falseyScopeCallback;
        $this->falseyScope = $callback();
        return $this->falseyScope;
    }
}
