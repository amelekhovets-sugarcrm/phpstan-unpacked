<?php

declare (strict_types=1);
namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\LastConditionVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;
use function strtolower;
/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class ImpossibleCheckTypeFunctionCallRule implements Rule
{
    /**
     * @var \PHPStan\Rules\Comparison\ImpossibleCheckTypeHelper
     */
    private $impossibleCheckTypeHelper;
    /**
     * @var bool
     */
    private $checkAlwaysTrueCheckTypeFunctionCall;
    /**
     * @var bool
     */
    private $treatPhpDocTypesAsCertain;
    /**
     * @var bool
     */
    private $reportAlwaysTrueInLastCondition;
    public function __construct(\PHPStan\Rules\Comparison\ImpossibleCheckTypeHelper $impossibleCheckTypeHelper, bool $checkAlwaysTrueCheckTypeFunctionCall, bool $treatPhpDocTypesAsCertain, bool $reportAlwaysTrueInLastCondition)
    {
        $this->impossibleCheckTypeHelper = $impossibleCheckTypeHelper;
        $this->checkAlwaysTrueCheckTypeFunctionCall = $checkAlwaysTrueCheckTypeFunctionCall;
        $this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
        $this->reportAlwaysTrueInLastCondition = $reportAlwaysTrueInLastCondition;
    }
    public function getNodeType() : string
    {
        return Node\Expr\FuncCall::class;
    }
    public function processNode(Node $node, Scope $scope) : array
    {
        if (!$node->name instanceof Node\Name) {
            return [];
        }
        $functionName = (string) $node->name;
        if (strtolower($functionName) === 'is_a') {
            return [];
        }
        $isAlways = $this->impossibleCheckTypeHelper->findSpecifiedType($scope, $node);
        if ($isAlways === null) {
            return [];
        }
        $addTip = function (RuleErrorBuilder $ruleErrorBuilder) use($scope, $node) : RuleErrorBuilder {
            if (!$this->treatPhpDocTypesAsCertain) {
                return $ruleErrorBuilder;
            }
            $isAlways = $this->impossibleCheckTypeHelper->doNotTreatPhpDocTypesAsCertain()->findSpecifiedType($scope, $node);
            if ($isAlways !== null) {
                return $ruleErrorBuilder;
            }
            return $ruleErrorBuilder->tip('Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.');
        };
        if (!$isAlways) {
            return [$addTip(RuleErrorBuilder::message(sprintf('Call to function %s()%s will always evaluate to false.', $functionName, $this->impossibleCheckTypeHelper->getArgumentsDescription($scope, $node->getArgs()))))->build()];
        } elseif ($this->checkAlwaysTrueCheckTypeFunctionCall) {
            $isLast = $node->getAttribute(LastConditionVisitor::ATTRIBUTE_NAME);
            if ($isLast === \true && !$this->reportAlwaysTrueInLastCondition) {
                return [];
            }
            $errorBuilder = $addTip(RuleErrorBuilder::message(sprintf('Call to function %s()%s will always evaluate to true.', $functionName, $this->impossibleCheckTypeHelper->getArgumentsDescription($scope, $node->getArgs()))));
            if ($isLast === \false && !$this->reportAlwaysTrueInLastCondition) {
                $errorBuilder->tip('Remove remaining cases below this one and this error will disappear too.');
            }
            return [$errorBuilder->build()];
        }
        return [];
    }
}
