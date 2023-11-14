<?php

declare (strict_types=1);
namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\TrinaryLogic;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
/**
 * @implements Rule<MethodReturnStatementsNode>
 */
class ThrowsVoidMethodWithExplicitThrowPointRule implements Rule
{
    /**
     * @var \PHPStan\Rules\Exceptions\ExceptionTypeResolver
     */
    private $exceptionTypeResolver;
    /**
     * @var bool
     */
    private $missingCheckedExceptionInThrows;
    public function __construct(\PHPStan\Rules\Exceptions\ExceptionTypeResolver $exceptionTypeResolver, bool $missingCheckedExceptionInThrows)
    {
        $this->exceptionTypeResolver = $exceptionTypeResolver;
        $this->missingCheckedExceptionInThrows = $missingCheckedExceptionInThrows;
    }
    public function getNodeType() : string
    {
        return MethodReturnStatementsNode::class;
    }
    public function processNode(Node $node, Scope $scope) : array
    {
        $statementResult = $node->getStatementResult();
        $methodReflection = $node->getMethodReflection();
        if ($methodReflection->getThrowType() === null || !$methodReflection->getThrowType()->isVoid()->yes()) {
            return [];
        }
        $errors = [];
        foreach ($statementResult->getThrowPoints() as $throwPoint) {
            if (!$throwPoint->isExplicit()) {
                continue;
            }
            foreach (TypeUtils::flattenTypes($throwPoint->getType()) as $throwPointType) {
                $isCheckedException = TrinaryLogic::createFromBoolean($this->missingCheckedExceptionInThrows)->lazyAnd($throwPointType->getObjectClassNames(), function (string $objectClassName) use($throwPoint) {
                    return TrinaryLogic::createFromBoolean($this->exceptionTypeResolver->isCheckedException($objectClassName, $throwPoint->getScope()));
                });
                if ($isCheckedException->yes()) {
                    continue;
                }
                $errors[] = RuleErrorBuilder::message(sprintf('Method %s::%s() throws exception %s but the PHPDoc contains @throws void.', $methodReflection->getDeclaringClass()->getDisplayName(), $methodReflection->getName(), $throwPointType->describe(VerbosityLevel::typeOnly())))->line($throwPoint->getNode()->getLine())->build();
            }
        }
        return $errors;
    }
}
