<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node\Expr\ArrayItem;
use PHPStan\Analyser\Scope;
/** @api */
class LiteralArrayItem
{
    /**
     * @var \PHPStan\Analyser\Scope
     */
    private $scope;
    /**
     * @var \PhpParser\Node\Expr\ArrayItem|null
     */
    private $arrayItem;
    public function __construct(Scope $scope, ?ArrayItem $arrayItem)
    {
        $this->scope = $scope;
        $this->arrayItem = $arrayItem;
    }
    public function getScope() : Scope
    {
        return $this->scope;
    }
    public function getArrayItem() : ?ArrayItem
    {
        return $this->arrayItem;
    }
}
