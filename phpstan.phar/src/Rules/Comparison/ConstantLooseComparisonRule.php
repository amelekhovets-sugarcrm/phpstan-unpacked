<?php

declare (strict_types=1);
namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\LastConditionVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
/**
 * @implements Rule<Node\Expr\BinaryOp>
 */
class ConstantLooseComparisonRule implements Rule
{
    /**
     * @var bool
     */
    private $checkAlwaysTrueLooseComparison;
    /**
     * @var bool
     */
    private $treatPhpDocTypesAsCertain;
    /**
     * @var bool
     */
    private $reportAlwaysTrueInLastCondition;
    public function __construct(bool $checkAlwaysTrueLooseComparison, bool $treatPhpDocTypesAsCertain, bool $reportAlwaysTrueInLastCondition)
    {
        $this->checkAlwaysTrueLooseComparison = $checkAlwaysTrueLooseComparison;
        $this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
        $this->reportAlwaysTrueInLastCondition = $reportAlwaysTrueInLastCondition;
    }
    public function getNodeType() : string
    {
        return Node\Expr\BinaryOp::class;
    }
    public function processNode(Node $node, Scope $scope) : array
    {
        if (!$node instanceof Node\Expr\BinaryOp\Equal && !$node instanceof Node\Expr\BinaryOp\NotEqual) {
            return [];
        }
        $nodeType = $this->treatPhpDocTypesAsCertain ? $scope->getType($node) : $scope->getNativeType($node);
        if (!$nodeType instanceof ConstantBooleanType) {
            return [];
        }
        $addTip = function (RuleErrorBuilder $ruleErrorBuilder) use($scope, $node) : RuleErrorBuilder {
            if (!$this->treatPhpDocTypesAsCertain) {
                return $ruleErrorBuilder;
            }
            $instanceofTypeWithoutPhpDocs = $scope->getNativeType($node);
            if ($instanceofTypeWithoutPhpDocs instanceof ConstantBooleanType) {
                return $ruleErrorBuilder;
            }
            return $ruleErrorBuilder->tip('Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.');
        };
        if (!$nodeType->getValue()) {
            return [$addTip(RuleErrorBuilder::message(sprintf('Loose comparison using %s between %s and %s will always evaluate to false.', $node instanceof Node\Expr\BinaryOp\Equal ? '==' : '!=', $scope->getType($node->left)->describe(VerbosityLevel::value()), $scope->getType($node->right)->describe(VerbosityLevel::value()))))->build()];
        } elseif ($this->checkAlwaysTrueLooseComparison) {
            $isLast = $node->getAttribute(LastConditionVisitor::ATTRIBUTE_NAME);
            if ($isLast === \true && !$this->reportAlwaysTrueInLastCondition) {
                return [];
            }
            $errorBuilder = $addTip(RuleErrorBuilder::message(sprintf('Loose comparison using %s between %s and %s will always evaluate to true.', $node instanceof Node\Expr\BinaryOp\Equal ? '==' : '!=', $scope->getType($node->left)->describe(VerbosityLevel::value()), $scope->getType($node->right)->describe(VerbosityLevel::value()))));
            if ($isLast === \false && !$this->reportAlwaysTrueInLastCondition) {
                $errorBuilder->tip('Remove remaining cases below this one and this error will disappear too.');
            }
            return [$errorBuilder->build()];
        }
        return [];
    }
}
