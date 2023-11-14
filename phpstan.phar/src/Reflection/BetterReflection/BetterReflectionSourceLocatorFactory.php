<?php

declare (strict_types=1);
namespace PHPStan\Reflection\BetterReflection;

use Phar;
use PhpParser\Parser;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber;
use PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Psr\Psr4Mapping;
use PHPStan\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\AutoloadFunctionsSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\AutoloadSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\ComposerJsonAndInstalledJsonSourceLocatorMaker;
use PHPStan\Reflection\BetterReflection\SourceLocator\FileNodesFetcher;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectorySourceLocatorRepository;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedPsrAutoloaderLocatorFactory;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorRepository;
use PHPStan\Reflection\BetterReflection\SourceLocator\PhpVersionBlacklistSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\ReflectionClassSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\RewriteClassAliasSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\SkipClassAliasSourceLocator;
use function array_merge;
use function array_unique;
use function extension_loaded;
use function is_dir;
use function is_file;
class BetterReflectionSourceLocatorFactory
{
    /**
     * @var \PhpParser\Parser
     */
    private $parser;
    /**
     * @var \PhpParser\Parser
     */
    private $php8Parser;
    /**
     * @var \PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber
     */
    private $phpstormStubsSourceStubber;
    /**
     * @var \PHPStan\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber
     */
    private $reflectionSourceStubber;
    /**
     * @var \PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorRepository
     */
    private $optimizedSingleFileSourceLocatorRepository;
    /**
     * @var \PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectorySourceLocatorRepository
     */
    private $optimizedDirectorySourceLocatorRepository;
    /**
     * @var \PHPStan\Reflection\BetterReflection\SourceLocator\ComposerJsonAndInstalledJsonSourceLocatorMaker
     */
    private $composerJsonAndInstalledJsonSourceLocatorMaker;
    /**
     * @var \PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedPsrAutoloaderLocatorFactory
     */
    private $optimizedPsrAutoloaderLocatorFactory;
    /**
     * @var \PHPStan\Reflection\BetterReflection\SourceLocator\FileNodesFetcher
     */
    private $fileNodesFetcher;
    /**
     * @var string[]
     */
    private $scanFiles;
    /**
     * @var string[]
     */
    private $scanDirectories;
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
     * @param string[] $scanFiles
     * @param string[] $scanDirectories
     * @param string[] $analysedPaths
     * @param string[] $composerAutoloaderProjectPaths
     * @param string[] $analysedPathsFromConfig
     */
    public function __construct(Parser $parser, Parser $php8Parser, PhpStormStubsSourceStubber $phpstormStubsSourceStubber, ReflectionSourceStubber $reflectionSourceStubber, OptimizedSingleFileSourceLocatorRepository $optimizedSingleFileSourceLocatorRepository, OptimizedDirectorySourceLocatorRepository $optimizedDirectorySourceLocatorRepository, ComposerJsonAndInstalledJsonSourceLocatorMaker $composerJsonAndInstalledJsonSourceLocatorMaker, OptimizedPsrAutoloaderLocatorFactory $optimizedPsrAutoloaderLocatorFactory, FileNodesFetcher $fileNodesFetcher, array $scanFiles, array $scanDirectories, array $analysedPaths, array $composerAutoloaderProjectPaths, array $analysedPathsFromConfig)
    {
        $this->parser = $parser;
        $this->php8Parser = $php8Parser;
        $this->phpstormStubsSourceStubber = $phpstormStubsSourceStubber;
        $this->reflectionSourceStubber = $reflectionSourceStubber;
        $this->optimizedSingleFileSourceLocatorRepository = $optimizedSingleFileSourceLocatorRepository;
        $this->optimizedDirectorySourceLocatorRepository = $optimizedDirectorySourceLocatorRepository;
        $this->composerJsonAndInstalledJsonSourceLocatorMaker = $composerJsonAndInstalledJsonSourceLocatorMaker;
        $this->optimizedPsrAutoloaderLocatorFactory = $optimizedPsrAutoloaderLocatorFactory;
        $this->fileNodesFetcher = $fileNodesFetcher;
        $this->scanFiles = $scanFiles;
        $this->scanDirectories = $scanDirectories;
        $this->analysedPaths = $analysedPaths;
        $this->composerAutoloaderProjectPaths = $composerAutoloaderProjectPaths;
        $this->analysedPathsFromConfig = $analysedPathsFromConfig;
    }
    public function create() : SourceLocator
    {
        $locators = [];
        $astLocator = new Locator($this->parser);
        $locators[] = new AutoloadFunctionsSourceLocator(new AutoloadSourceLocator($this->fileNodesFetcher, \false), new ReflectionClassSourceLocator($astLocator, $this->reflectionSourceStubber));
        $analysedDirectories = [];
        $analysedFiles = [];
        foreach (array_merge($this->analysedPaths, $this->analysedPathsFromConfig) as $analysedPath) {
            if (is_file($analysedPath)) {
                $analysedFiles[] = $analysedPath;
                continue;
            }
            if (!is_dir($analysedPath)) {
                continue;
            }
            $analysedDirectories[] = $analysedPath;
        }
        $fileLocators = [];
        $analysedFiles = array_unique(array_merge($analysedFiles, $this->scanFiles));
        foreach ($analysedFiles as $analysedFile) {
            $fileLocators[] = $this->optimizedSingleFileSourceLocatorRepository->getOrCreate($analysedFile);
        }
        $directories = array_unique(array_merge($analysedDirectories, $this->scanDirectories));
        foreach ($directories as $directory) {
            $fileLocators[] = $this->optimizedDirectorySourceLocatorRepository->getOrCreate($directory);
        }
        $astPhp8Locator = new Locator($this->php8Parser);
        foreach ($this->composerAutoloaderProjectPaths as $composerAutoloaderProjectPath) {
            $locator = $this->composerJsonAndInstalledJsonSourceLocatorMaker->create($composerAutoloaderProjectPath);
            if ($locator === null) {
                continue;
            }
            $fileLocators[] = $locator;
        }
        if (extension_loaded('phar')) {
            $pharProtocolPath = Phar::running();
            if ($pharProtocolPath !== '') {
                $fileLocators[] = $this->optimizedPsrAutoloaderLocatorFactory->create(Psr4Mapping::fromArrayMappings(['PHPStan\\Testing\\' => [$pharProtocolPath . '/src/Testing/'], 'PHPStan\\BetterReflection\\' => [$pharProtocolPath . '/vendor/ondrejmirtes/better-reflection/src/']]));
            }
        }
        $locators[] = new RewriteClassAliasSourceLocator(new AggregateSourceLocator($fileLocators));
        $locators[] = new SkipClassAliasSourceLocator(new PhpInternalSourceLocator($astPhp8Locator, $this->phpstormStubsSourceStubber));
        $locators[] = new AutoloadSourceLocator($this->fileNodesFetcher, \true);
        $locators[] = new PhpVersionBlacklistSourceLocator(new PhpInternalSourceLocator($astLocator, $this->reflectionSourceStubber), $this->phpstormStubsSourceStubber);
        $locators[] = new PhpVersionBlacklistSourceLocator(new EvaledCodeSourceLocator($astLocator, $this->reflectionSourceStubber), $this->phpstormStubsSourceStubber);
        return new MemoizingSourceLocator(new AggregateSourceLocator($locators));
    }
}
