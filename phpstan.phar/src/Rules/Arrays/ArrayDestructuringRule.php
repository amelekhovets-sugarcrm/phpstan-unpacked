<?php

declare (strict_types=1);
namespace PHPStan\Rules\Arrays;

use ArrayAccess;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function sprintf;
/**
 * @implements Rule<Assign>
 */
class ArrayDestructuringRule implements Rule
{
    /**
     * @var \PHPStan\Rules\RuleLevelHelper
     */
    private $ruleLevelHelper;
    /**
     * @var \PHPStan\Rules\Arrays\NonexistentOffsetInArrayDimFetchCheck
     */
    private $nonexistentOffsetInArrayDimFetchCheck;
    public function __construct(RuleLevelHelper $ruleLevelHelper, \PHPStan\Rules\Arrays\NonexistentOffsetInArrayDimFetchCheck $nonexistentOffsetInArrayDimFetchCheck)
    {
        $this->ruleLevelHelper = $ruleLevelHelper;
        $this->nonexistentOffsetInArrayDimFetchCheck = $nonexistentOffsetInArrayDimFetchCheck;
    }
    public function getNodeType() : string
    {
        return Assign::class;
    }
    public function processNode(Node $node, Scope $scope) : array
    {
        if (!$node->var instanceof Node\Expr\List_ && !$node->var instanceof Node\Expr\Array_) {
            return [];
        }
        return $this->getErrors($scope, $node->var, $node->expr);
    }
    /**
     * @param Node\Expr\List_|Node\Expr\Array_ $var
     * @return RuleError[]
     */
    private function getErrors(Scope $scope, Expr $var, Expr $expr) : array
    {
        $exprTypeResult = $this->ruleLevelHelper->findTypeToCheck($scope, $expr, '', static function (Type $varType) : bool {
            return $varType->isArray()->yes() || (new ObjectType(ArrayAccess::class))->isSuperTypeOf($varType)->yes();
        });
        $exprType = $exprTypeResult->getType();
        if ($exprType instanceof ErrorType) {
            return [];
        }
        if (!$exprType->isArray()->yes() && !(new ObjectType(ArrayAccess::class))->isSuperTypeOf($exprType)->yes()) {
            return [RuleErrorBuilder::message(sprintf('Cannot use array destructuring on %s.', $exprType->describe(VerbosityLevel::typeOnly())))->build()];
        }
        $errors = [];
        $i = 0;
        foreach ($var->items as $item) {
            if ($item === null) {
                $i++;
                continue;
            }
            $keyExpr = null;
            if ($item->key === null) {
                $keyType = new ConstantIntegerType($i);
                $keyExpr = new Node\Scalar\LNumber($i);
            } else {
                $keyType = $scope->getType($item->key);
                $keyExpr = new TypeExpr($keyType);
            }
            $itemErrors = $this->nonexistentOffsetInArrayDimFetchCheck->check($scope, $expr, '', $keyType);
            $errors = array_merge($errors, $itemErrors);
            if (!$item->value instanceof Node\Expr\List_ && !$item->value instanceof Node\Expr\Array_) {
                $i++;
                continue;
            }
            $errors = array_merge($errors, $this->getErrors($scope, $item->value, new Expr\ArrayDimFetch($expr, $keyExpr)));
        }
        return $errors;
    }
}