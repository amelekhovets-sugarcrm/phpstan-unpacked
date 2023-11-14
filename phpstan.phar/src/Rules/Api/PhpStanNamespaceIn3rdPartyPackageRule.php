<?php

declare (strict_types=1);
namespace PHPStan\Rules\Api;

use _PHPStan_c6b09fbdf\Nette\Utils\Json;
use _PHPStan_c6b09fbdf\Nette\Utils\JsonException;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\File\CouldNotReadFileException;
use PHPStan\File\FileReader;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function dirname;
use function is_dir;
use function is_file;
use function strpos;
/**
 * @implements Rule<Node\Stmt\Namespace_>
 */
class PhpStanNamespaceIn3rdPartyPackageRule implements Rule
{
    /**
     * @var \PHPStan\Rules\Api\ApiRuleHelper
     */
    private $apiRuleHelper;
    public function __construct(\PHPStan\Rules\Api\ApiRuleHelper $apiRuleHelper)
    {
        $this->apiRuleHelper = $apiRuleHelper;
    }
    public function getNodeType() : string
    {
        return Node\Stmt\Namespace_::class;
    }
    public function processNode(Node $node, Scope $scope) : array
    {
        $namespace = null;
        if ($node->name !== null) {
            $namespace = $node->name->toString();
        }
        if ($namespace === null || !$this->apiRuleHelper->isPhpStanName($namespace)) {
            return [];
        }
        $composerJson = $this->findComposerJsonContents(dirname($scope->getFile()));
        if ($composerJson === null) {
            return [];
        }
        $packageName = $composerJson['name'] ?? null;
        if ($packageName !== null && strpos($packageName, 'phpstan/') === 0) {
            return [];
        }
        return [RuleErrorBuilder::message('Declaring PHPStan namespace is not allowed in 3rd party packages.')->tip("See:\n   https://phpstan.org/developing-extensions/backward-compatibility-promise")->build()];
    }
    /**
     * @return mixed[]|null
     */
    private function findComposerJsonContents(string $fromDirectory) : ?array
    {
        if (!is_dir($fromDirectory)) {
            return null;
        }
        $composerJsonPath = $fromDirectory . '/composer.json';
        if (!is_file($composerJsonPath)) {
            $dirName = dirname($fromDirectory);
            if ($dirName !== $fromDirectory) {
                return $this->findComposerJsonContents($dirName);
            }
            return null;
        }
        try {
            return Json::decode(FileReader::read($composerJsonPath), Json::FORCE_ARRAY);
        } catch (JsonException $exception) {
            return null;
        } catch (CouldNotReadFileException $exception) {
            return null;
        }
    }
}
