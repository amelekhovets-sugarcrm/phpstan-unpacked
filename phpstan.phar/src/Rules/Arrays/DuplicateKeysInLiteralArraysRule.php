<?php

declare (strict_types=1);
namespace PHPStan\Rules\Arrays;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\LiteralArrayNode;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ConstantScalarType;
use function array_keys;
use function count;
use function implode;
use function sprintf;
use function var_export;
/**
 * @implements Rule<LiteralArrayNode>
 */
class DuplicateKeysInLiteralArraysRule implements Rule
{
    /**
     * @var \PHPStan\Node\Printer\ExprPrinter
     */
    private $exprPrinter;
    public function __construct(ExprPrinter $exprPrinter)
    {
        $this->exprPrinter = $exprPrinter;
    }
    public function getNodeType() : string
    {
        return LiteralArrayNode::class;
    }
    public function processNode(Node $node, Scope $scope) : array
    {
        $values = [];
        $duplicateKeys = [];
        $printedValues = [];
        $valueLines = [];
        foreach ($node->getItemNodes() as $itemNode) {
            $item = $itemNode->getArrayItem();
            if ($item === null) {
                continue;
            }
            if ($item->key === null) {
                continue;
            }
            $key = $item->key;
            $keyType = $itemNode->getScope()->getType($key);
            if (!$keyType instanceof ConstantScalarType) {
                continue;
            }
            $printedValue = $this->exprPrinter->printExpr($key);
            $value = $keyType->getValue();
            $printedValues[$value][] = $printedValue;
            if (!isset($valueLines[$value])) {
                $valueLines[$value] = $item->getLine();
            }
            $previousCount = count($values);
            $values[$value] = $printedValue;
            if ($previousCount !== count($values)) {
                continue;
            }
            $duplicateKeys[$value] = \true;
        }
        $messages = [];
        foreach (array_keys($duplicateKeys) as $value) {
            $messages[] = RuleErrorBuilder::message(sprintf('Array has %d %s with value %s (%s).', count($printedValues[$value]), count($printedValues[$value]) === 1 ? 'duplicate key' : 'duplicate keys', var_export($value, \true), implode(', ', $printedValues[$value])))->line($valueLines[$value])->build();
        }
        return $messages;
    }
}