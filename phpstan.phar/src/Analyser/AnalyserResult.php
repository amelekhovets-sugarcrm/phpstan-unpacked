<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\Collectors\CollectedData;
use PHPStan\Dependency\RootExportedNode;
use function usort;
class AnalyserResult
{
    /**
     * @var list<Error>
     */
    private $unorderedErrors;
    /**
     * @var list<string>
     */
    private $internalErrors;
    /**
     * @var list<CollectedData>
     */
    private $collectedData;
    /**
     * @var array<string, array<string>>|null
     */
    private $dependencies;
    /**
     * @var array<string, array<RootExportedNode>>
     */
    private $exportedNodes;
    /**
     * @var bool
     */
    private $reachedInternalErrorsCountLimit;
    /**
     * @var int
     */
    private $peakMemoryUsageBytes;
    /** @var list<Error>|null */
    private $errors;
    /**
     * @param list<Error> $unorderedErrors
     * @param list<CollectedData> $collectedData
     * @param list<string> $internalErrors
     * @param array<string, array<string>>|null $dependencies
     * @param array<string, array<RootExportedNode>> $exportedNodes
     */
    public function __construct(array $unorderedErrors, array $internalErrors, array $collectedData, ?array $dependencies, array $exportedNodes, bool $reachedInternalErrorsCountLimit, int $peakMemoryUsageBytes)
    {
        $this->unorderedErrors = $unorderedErrors;
        $this->internalErrors = $internalErrors;
        $this->collectedData = $collectedData;
        $this->dependencies = $dependencies;
        $this->exportedNodes = $exportedNodes;
        $this->reachedInternalErrorsCountLimit = $reachedInternalErrorsCountLimit;
        $this->peakMemoryUsageBytes = $peakMemoryUsageBytes;
    }
    /**
     * @return list<Error>
     */
    public function getUnorderedErrors() : array
    {
        return $this->unorderedErrors;
    }
    /**
     * @return list<Error>
     */
    public function getErrors() : array
    {
        if (!isset($this->errors)) {
            $this->errors = $this->unorderedErrors;
            usort($this->errors, static function (\PHPStan\Analyser\Error $a, \PHPStan\Analyser\Error $b) : int {
                return [$a->getFile(), $a->getLine(), $a->getMessage()] <=> [$b->getFile(), $b->getLine(), $b->getMessage()];
            });
        }
        return $this->errors;
    }
    /**
     * @return list<string>
     */
    public function getInternalErrors() : array
    {
        return $this->internalErrors;
    }
    /**
     * @return list<CollectedData>
     */
    public function getCollectedData() : array
    {
        return $this->collectedData;
    }
    /**
     * @return array<string, array<string>>|null
     */
    public function getDependencies() : ?array
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
    public function hasReachedInternalErrorsCountLimit() : bool
    {
        return $this->reachedInternalErrorsCountLimit;
    }
    public function getPeakMemoryUsageBytes() : int
    {
        return $this->peakMemoryUsageBytes;
    }
}
