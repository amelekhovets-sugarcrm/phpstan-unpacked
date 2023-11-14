<?php

declare (strict_types=1);
namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\File\RelativePathHelper;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_filter;
use function array_map;
use function count;
use function implode;
use function sprintf;
/**
 * @implements Rule<InClassNode>
 */
class DuplicateClassDeclarationRule implements Rule
{
    /**
     * @var \PHPStan\BetterReflection\Reflector\Reflector
     */
    private $reflector;
    /**
     * @var \PHPStan\File\RelativePathHelper
     */
    private $relativePathHelper;
    public function __construct(Reflector $reflector, RelativePathHelper $relativePathHelper)
    {
        $this->reflector = $reflector;
        $this->relativePathHelper = $relativePathHelper;
    }
    public function getNodeType() : string
    {
        return InClassNode::class;
    }
    public function processNode(Node $node, Scope $scope) : array
    {
        $thisClass = $node->getClassReflection();
        $className = $thisClass->getName();
        $allClasses = $this->reflector->reflectAllClasses();
        $filteredClasses = [];
        foreach ($allClasses as $reflectionClass) {
            if ($reflectionClass->getName() !== $className) {
                continue;
            }
            $filteredClasses[] = $reflectionClass;
        }
        if (count($filteredClasses) < 2) {
            return [];
        }
        $filteredClasses = array_filter($filteredClasses, static function (ReflectionClass $class) use($thisClass) {
            return $class->getStartLine() !== $thisClass->getNativeReflection()->getStartLine();
        });
        return [RuleErrorBuilder::message(sprintf("Class %s declared multiple times:\n%s", $thisClass->getDisplayName(), implode("\n", array_map(function (ReflectionClass $class) {
            return sprintf('- %s:%d', $this->relativePathHelper->getRelativePath($class->getFileName() ?? 'unknown'), $class->getStartLine());
        }, $filteredClasses))))->build()];
    }
}
