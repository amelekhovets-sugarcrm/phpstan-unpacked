<?php

declare (strict_types=1);
namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_map;
use function sprintf;
/**
 * @implements Rule<Node\Stmt\Enum_>
 */
class ExistingClassesInEnumImplementsRule implements Rule
{
    /**
     * @var \PHPStan\Rules\ClassCaseSensitivityCheck
     */
    private $classCaseSensitivityCheck;
    /**
     * @var \PHPStan\Reflection\ReflectionProvider
     */
    private $reflectionProvider;
    public function __construct(ClassCaseSensitivityCheck $classCaseSensitivityCheck, ReflectionProvider $reflectionProvider)
    {
        $this->classCaseSensitivityCheck = $classCaseSensitivityCheck;
        $this->reflectionProvider = $reflectionProvider;
    }
    public function getNodeType() : string
    {
        return Node\Stmt\Enum_::class;
    }
    public function processNode(Node $node, Scope $scope) : array
    {
        $messages = $this->classCaseSensitivityCheck->checkClassNames(array_map(static function (Node\Name $interfaceName) : ClassNameNodePair {
            return new ClassNameNodePair((string) $interfaceName, $interfaceName);
        }, $node->implements));
        $currentEnumName = (string) $node->namespacedName;
        foreach ($node->implements as $implements) {
            $implementedClassName = (string) $implements;
            if (!$this->reflectionProvider->hasClass($implementedClassName)) {
                if (!$scope->isInClassExists($implementedClassName)) {
                    $messages[] = RuleErrorBuilder::message(sprintf('Enum %s implements unknown interface %s.', $currentEnumName, $implementedClassName))->nonIgnorable()->discoveringSymbolsTip()->build();
                }
            } else {
                $reflection = $this->reflectionProvider->getClass($implementedClassName);
                if ($reflection->isClass()) {
                    $messages[] = RuleErrorBuilder::message(sprintf('Enum %s implements class %s.', $currentEnumName, $reflection->getDisplayName()))->nonIgnorable()->build();
                } elseif ($reflection->isTrait()) {
                    $messages[] = RuleErrorBuilder::message(sprintf('Enum %s implements trait %s.', $currentEnumName, $reflection->getDisplayName()))->nonIgnorable()->build();
                } elseif ($reflection->isEnum()) {
                    $messages[] = RuleErrorBuilder::message(sprintf('Enum %s implements enum %s.', $currentEnumName, $reflection->getDisplayName()))->nonIgnorable()->build();
                }
            }
        }
        return $messages;
    }
}
