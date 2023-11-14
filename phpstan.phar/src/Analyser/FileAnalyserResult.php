<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\Collectors\CollectedData;
use PHPStan\Dependency\RootExportedNode;
class FileAnalyserResult
{
    /**
     * @var list<Error>
     */
    private $errors;
    /**
     * @var list<CollectedData>
     */
    private $collectedData;
    /**
     * @var list<string>
     */
    private $dependencies;
    /**
     * @var list<RootExportedNode>
     */
    private $exportedNodes;
    /**
     * @param list<Error> $errors
     * @param list<CollectedData> $collectedData
     * @param list<string> $dependencies
     * @param list<RootExportedNode> $exportedNodes
     */
    public function __construct(array $errors, array $collectedData, array $dependencies, array $exportedNodes)
    {
        $this->errors = $errors;
        $this->collectedData = $collectedData;
        $this->dependencies = $dependencies;
        $this->exportedNodes = $exportedNodes;
    }
    /**
     * @return list<Error>
     */
    public function getErrors() : array
    {
        return $this->errors;
    }
    /**
     * @return list<CollectedData>
     */
    public function getCollectedData() : array
    {
        return $this->collectedData;
    }
    /**
     * @return list<string>
     */
    public function getDependencies() : array
    {
        return $this->dependencies;
    }
    /**
     * @return list<RootExportedNode>
     */
    public function getExportedNodes() : array
    {
        return $this->exportedNodes;
    }
}
