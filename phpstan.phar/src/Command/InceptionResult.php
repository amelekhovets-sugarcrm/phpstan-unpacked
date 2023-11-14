<?php

declare (strict_types=1);
namespace PHPStan\Command;

use PHPStan\DependencyInjection\Container;
use PHPStan\File\PathNotFoundException;
use PHPStan\Internal\BytesHelper;
use function max;
use function memory_get_peak_usage;
use function sprintf;
class InceptionResult
{
    /**
     * @var \PHPStan\Command\Output
     */
    private $stdOutput;
    /**
     * @var \PHPStan\Command\Output
     */
    private $errorOutput;
    /**
     * @var \PHPStan\DependencyInjection\Container
     */
    private $container;
    /**
     * @var bool
     */
    private $isDefaultLevelUsed;
    /**
     * @var string|null
     */
    private $projectConfigFile;
    /**
     * @var mixed[]|null
     */
    private $projectConfigArray;
    /**
     * @var string|null
     */
    private $generateBaselineFile;
    /** @var callable(): (array{string[], bool}) */
    private $filesCallback;
    /**
     * @param callable(): (array{string[], bool}) $filesCallback
     * @param mixed[]|null $projectConfigArray
     */
    public function __construct(callable $filesCallback, \PHPStan\Command\Output $stdOutput, \PHPStan\Command\Output $errorOutput, Container $container, bool $isDefaultLevelUsed, ?string $projectConfigFile, ?array $projectConfigArray, ?string $generateBaselineFile)
    {
        $this->stdOutput = $stdOutput;
        $this->errorOutput = $errorOutput;
        $this->container = $container;
        $this->isDefaultLevelUsed = $isDefaultLevelUsed;
        $this->projectConfigFile = $projectConfigFile;
        $this->projectConfigArray = $projectConfigArray;
        $this->generateBaselineFile = $generateBaselineFile;
        $this->filesCallback = $filesCallback;
    }
    /**
     * @throws InceptionNotSuccessfulException
     * @throws PathNotFoundException
     * @return array{string[], bool}
     */
    public function getFiles() : array
    {
        $callback = $this->filesCallback;
        /** @throws InceptionNotSuccessfulException|PathNotFoundException */
        return $callback();
    }
    public function getStdOutput() : \PHPStan\Command\Output
    {
        return $this->stdOutput;
    }
    public function getErrorOutput() : \PHPStan\Command\Output
    {
        return $this->errorOutput;
    }
    public function getContainer() : Container
    {
        return $this->container;
    }
    public function isDefaultLevelUsed() : bool
    {
        return $this->isDefaultLevelUsed;
    }
    public function getProjectConfigFile() : ?string
    {
        return $this->projectConfigFile;
    }
    /**
     * @return mixed[]|null
     */
    public function getProjectConfigArray() : ?array
    {
        return $this->projectConfigArray;
    }
    public function getGenerateBaselineFile() : ?string
    {
        return $this->generateBaselineFile;
    }
    public function handleReturn(int $exitCode, ?int $peakMemoryUsageBytes) : int
    {
        if ($peakMemoryUsageBytes !== null && $this->getErrorOutput()->isVerbose()) {
            $this->getErrorOutput()->writeLineFormatted(sprintf('Used memory: %s', BytesHelper::bytes(max(memory_get_peak_usage(\true), $peakMemoryUsageBytes))));
        }
        return $exitCode;
    }
}
