<?php

declare (strict_types=1);
namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\LastConditionVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use function sprintf;
/**
 * @implements Rule<Node\Stmt\ElseIf_>
 */
class ElseIfConstantConditionRule implements Rule
{
    /**
     * @var \PHPStan\Rules\Comparison\ConstantConditionRuleHelper
     */
    private $helper;
    /**
     * @var bool
     */
    private $treatPhpDocTypesAsCertain;
    /**
     * @var bool
     */
    private $reportAlwaysTrueInLastCondition;
    public function __construct(\PHPStan\Rules\Comparison\ConstantConditionRuleHelper $helper, bool $treatPhpDocTypesAsCertain, bool $reportAlwaysTrueInLastCondition)
    {
        $this->helper = $helper;
        $this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
        $this->reportAlwaysTrueInLastCondition = $reportAlwaysTrueInLastCondition;
    }
    public function getNodeType() : string
    {
        return Node\Stmt\ElseIf_::class;
    }
    public function processNode(Node $node, Scope $scope) : array
    {
        $exprType = $this->helper->getBooleanType($scope, $node->cond);
        if ($exprType instanceof ConstantBooleanType) {
            $addTip = function (RuleErrorBuilder $ruleErrorBuilder) use($scope, $node) : RuleErrorBuilder {
                if (!$this->treatPhpDocTypesAsCertain) {
                    return $ruleErrorBuilder;
                }
                $booleanNativeType = $this->helper->getNativeBooleanType($scope, $node->cond);
                if ($booleanNativeType instanceof ConstantBooleanType) {
                    return $ruleErrorBuilder;
                }
                return $ruleErrorBuilder->tip('Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.');
            };
            $isLast = $node->cond->getAttribute(LastConditionVisitor::ATTRIBUTE_NAME);
            if (!$exprType->getValue() || $isLast !== \true || $this->reportAlwaysTrueInLastCondition) {
                $errorBuilder = $addTip(RuleErrorBuilder::message(sprintf('Elseif condition is always %s.', $exprType->getValue() ? 'true' : 'false')))->line($node->cond->getLine())->identifier('deadCode.elseifConstantCondition')->metadata(['depth' => $node->getAttribute('statementDepth'), 'order' => $node->getAttribute('statementOrder'), 'value' => $exprType->getValue()]);
                if ($exprType->getValue() && $isLast === \false && !$this->reportAlwaysTrueInLastCondition) {
                    $errorBuilder->tip('Remove remaining cases below this one and this error will disappear too.');
                }
                return [$errorBuilder->build()];
            }
        }
        return [];
    }
}
