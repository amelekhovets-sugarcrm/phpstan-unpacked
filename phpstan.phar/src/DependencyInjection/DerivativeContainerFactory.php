<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection;

use function array_merge;
class DerivativeContainerFactory
{
    /**
     * @var string
     */
    private $currentWorkingDirectory;
    /**
     * @var string
     */
    private $tempDirectory;
    /**
     * @var string[]
     */
    private $additionalConfigFiles;
    /**
     * @var string[]
     */
    private $analysedPaths;
    /**
     * @var string[]
     */
    private $composerAutoloaderProjectPaths;
    /**
     * @var string[]
     */
    private $analysedPathsFromConfig;
    /**
     * @var string
     */
    private $usedLevel;
    /**
     * @var string|null
     */
    private $generateBaselineFile;
    /**
     * @var string|null
     */
    private $cliAutoloadFile;
    /**
     * @param string[] $additionalConfigFiles
     * @param string[] $analysedPaths
     * @param string[] $composerAutoloaderProjectPaths
     * @param string[] $analysedPathsFromConfig
     */
    public function __construct(string $currentWorkingDirectory, string $tempDirectory, array $additionalConfigFiles, array $analysedPaths, array $composerAutoloaderProjectPaths, array $analysedPathsFromConfig, string $usedLevel, ?string $generateBaselineFile, ?string $cliAutoloadFile)
    {
        $this->currentWorkingDirectory = $currentWorkingDirectory;
        $this->tempDirectory = $tempDirectory;
        $this->additionalConfigFiles = $additionalConfigFiles;
        $this->analysedPaths = $analysedPaths;
        $this->composerAutoloaderProjectPaths = $composerAutoloaderProjectPaths;
        $this->analysedPathsFromConfig = $analysedPathsFromConfig;
        $this->usedLevel = $usedLevel;
        $this->generateBaselineFile = $generateBaselineFile;
        $this->cliAutoloadFile = $cliAutoloadFile;
    }
    /**
     * @param string[] $additionalConfigFiles
     */
    public function create(array $additionalConfigFiles) : \PHPStan\DependencyInjection\Container
    {
        $containerFactory = new \PHPStan\DependencyInjection\ContainerFactory($this->currentWorkingDirectory);
        return $containerFactory->create($this->tempDirectory, array_merge($this->additionalConfigFiles, $additionalConfigFiles), $this->analysedPaths, $this->composerAutoloaderProjectPaths, $this->analysedPathsFromConfig, $this->usedLevel, $this->generateBaselineFile, $this->cliAutoloadFile);
    }
}
