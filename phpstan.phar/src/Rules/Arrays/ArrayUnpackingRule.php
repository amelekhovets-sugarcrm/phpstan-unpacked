<?php

declare (strict_types=1);
namespace PHPStan\Rules\Arrays;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrayItem;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Expr\GetIterableKeyTypeExpr;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
/**
 * @implements Rule<ArrayItem>
 */
class ArrayUnpackingRule implements Rule
{
    /**
     * @var \PHPStan\Php\PhpVersion
     */
    private $phpVersion;
    /**
     * @var \PHPStan\Rules\RuleLevelHelper
     */
    private $ruleLevelHelper;
    public function __construct(PhpVersion $phpVersion, RuleLevelHelper $ruleLevelHelper)
    {
        $this->phpVersion = $phpVersion;
        $this->ruleLevelHelper = $ruleLevelHelper;
    }
    public function getNodeType() : string
    {
        return ArrayItem::class;
    }
    public function processNode(Node $node, Scope $scope) : array
    {
        if ($node->unpack === \false || $this->phpVersion->supportsArrayUnpackingWithStringKeys()) {
            return [];
        }
        $typeResult = $this->ruleLevelHelper->findTypeToCheck($scope, new GetIterableKeyTypeExpr($node->value), '', static function (Type $type) : bool {
            return $type->isString()->no();
        });
        $keyType = $typeResult->getType();
        if ($keyType instanceof ErrorType) {
            return $typeResult->getUnknownClassErrors();
        }
        $isString = $keyType->isString();
        if ($isString->no()) {
            return [];
        }
        return [RuleErrorBuilder::message(sprintf('Array unpacking cannot be used on an array with %sstring keys: %s', $isString->yes() ? '' : 'potential ', $scope->getType($node->value)->describe(VerbosityLevel::value())))->build()];
    }
}
