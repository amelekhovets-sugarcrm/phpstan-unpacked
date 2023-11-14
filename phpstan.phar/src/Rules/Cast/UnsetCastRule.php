<?php

declare (strict_types=1);
namespace PHPStan\Rules\Cast;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
/**
 * @implements Rule<Node\Expr\Cast\Unset_>
 */
class UnsetCastRule implements Rule
{
    /**
     * @var \PHPStan\Php\PhpVersion
     */
    private $phpVersion;
    public function __construct(PhpVersion $phpVersion)
    {
        $this->phpVersion = $phpVersion;
    }
    public function getNodeType() : string
    {
        return Node\Expr\Cast\Unset_::class;
    }
    public function processNode(Node $node, Scope $scope) : array
    {
        if ($this->phpVersion->supportsUnsetCast()) {
            return [];
        }
        return [RuleErrorBuilder::message('The (unset) cast is no longer supported in PHP 8.0 and later.')->nonIgnorable()->build()];
    }
}