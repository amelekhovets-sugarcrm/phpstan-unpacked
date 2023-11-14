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
 * @implements Rule<Node\Stmt\Interface_>
 */
class ExistingClassesInInterfaceExtendsRule implements Rule
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
        return Node\Stmt\Interface_::class;
    }
    public function processNode(Node $node, Scope $scope) : array
    {
        $messages = $this->classCaseSensitivityCheck->checkClassNames(array_map(static function (Node\Name $interfaceName) : ClassNameNodePair {
            return new ClassNameNodePair((string) $interfaceName, $interfaceName);
        }, $node->extends));
        $currentInterfaceName = (string) $node->namespacedName;
        foreach ($node->extends as $extends) {
            $extendedInterfaceName = (string) $extends;
            if (!$this->reflectionProvider->hasClass($extendedInterfaceName)) {
                if (!$scope->isInClassExists($extendedInterfaceName)) {
                    $messages[] = RuleErrorBuilder::message(sprintf('Interface %s extends unknown interface %s.', $currentInterfaceName, $extendedInterfaceName))->nonIgnorable()->discoveringSymbolsTip()->build();
                }
            } else {
                $reflection = $this->reflectionProvider->getClass($extendedInterfaceName);
                if ($reflection->isClass()) {
                    $messages[] = RuleErrorBuilder::message(sprintf('Interface %s extends class %s.', $currentInterfaceName, $reflection->getDisplayName()))->nonIgnorable()->build();
                } elseif ($reflection->isTrait()) {
                    $messages[] = RuleErrorBuilder::message(sprintf('Interface %s extends trait %s.', $currentInterfaceName, $reflection->getDisplayName()))->nonIgnorable()->build();
                } elseif ($reflection->isEnum()) {
                    $messages[] = RuleErrorBuilder::message(sprintf('Interface %s extends enum %s.', $currentInterfaceName, $reflection->getDisplayName()))->nonIgnorable()->build();
                }
            }
            return $messages;
        }
        return $messages;
    }
}
