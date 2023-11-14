<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\AnalysedCodeException;
use function sprintf;
class UndefinedVariableException extends AnalysedCodeException
{
    /**
     * @var \PHPStan\Analyser\Scope
     */
    private $scope;
    /**
     * @var string
     */
    private $variableName;
    public function __construct(\PHPStan\Analyser\Scope $scope, string $variableName)
    {
        $this->scope = $scope;
        $this->variableName = $variableName;
        parent::__construct(sprintf('Undefined variable: $%s', $variableName));
    }
    public function getScope() : \PHPStan\Analyser\Scope
    {
        return $this->scope;
    }
    public function getVariableName() : string
    {
        return $this->variableName;
    }
    public function getTip() : ?string
    {
        return null;
    }
}
