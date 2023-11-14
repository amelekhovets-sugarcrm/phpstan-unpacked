<?php

declare (strict_types=1);
namespace PHPStan\Analyser\ResultCache;

use PHPStan\Analyser\Error;
use PHPStan\Collectors\CollectedData;
use PHPStan\Dependency\RootExportedNode;
class ResultCache
{
    /**
     * @var string[]
     */
    private $filesToAnalyse;
    /**
     * @var bool
     */
    private $fullAnalysis;
    /**
     * @var int
     */
    private $lastFullAnalysisTime;
    /**
     * @var mixed[]
     */
    private $meta;
    /**
     * @var array<string, array<Error>>
     */
    private $errors;
    /**
     * @var array<string, array<CollectedData>>
     */
    private $collectedData;
    /**
     * @var array<string, array<string>>
     */
    private $dependencies;
    /**
     * @var array<string, array<RootExportedNode>>
     */
    private $exportedNodes;
    /**
     * @param string[] $filesToAnalyse
     * @param mixed[] $meta
     * @param array<string, array<Error>> $errors
     * @param array<string, array<CollectedData>> $collectedData
     * @param array<string, array<string>> $dependencies
     * @param array<string, array<RootExportedNode>> $exportedNodes
     */
    public function __construct(array $filesToAnalyse, bool $fullAnalysis, int $lastFullAnalysisTime, array $meta, array $errors, array $collectedData, array $dependencies, array $exportedNodes)
    {
        $this->filesToAnalyse = $filesToAnalyse;
        $this->fullAnalysis = $fullAnalysis;
        $this->lastFullAnalysisTime = $lastFullAnalysisTime;
        $this->meta = $meta;
        $this->errors = $errors;
        $this->collectedData = $collectedData;
        $this->dependencies = $dependencies;
        $this->exportedNodes = $exportedNodes;
    }
    /**
     * @return string[]
     */
    public function getFilesToAnalyse() : array
    {
        return $this->filesToAnalyse;
    }
    public function isFullAnalysis() : bool
    {
        return $this->fullAnalysis;
    }
    public function getLastFullAnalysisTime() : int
    {
        return $this->lastFullAnalysisTime;
    }
    /**
     * @return mixed[]
     */
    public function getMeta() : array
    {
        return $this->meta;
    }
    /**
     * @return array<string, array<Error>>
     */
    public function getErrors() : array
    {
        return $this->errors;
    }
    /**
     * @return array<string, array<CollectedData>>
     */
    public function getCollectedData() : array
    {
        return $this->collectedData;
    }
    /**
     * @return array<string, array<string>>
     */
    public function getDependencies() : array
    {
        return $this->dependencies;
    }
    /**
     * @return array<string, array<RootExportedNode>>
     */
    public function getExportedNodes() : array
    {
        return $this->exportedNodes;
    }
}
