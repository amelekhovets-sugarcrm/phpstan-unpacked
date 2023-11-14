<?php

declare (strict_types=1);
namespace PHPStan\Command;

use PHPStan\AnalysedCodeException;
use PHPStan\Analyser\AnalyserResult;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoredErrorHelper;
use PHPStan\Analyser\ResultCache\ResultCacheManagerFactory;
use PHPStan\Analyser\RuleErrorTransformer;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use PHPStan\BetterReflection\Reflection\Exception\CircularReference;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\Collectors\CollectedData;
use PHPStan\Internal\BytesHelper;
use PHPStan\Node\CollectedDataNode;
use PHPStan\PhpDoc\StubFilesProvider;
use PHPStan\PhpDoc\StubValidator;
use PHPStan\Rules\Registry as RuleRegistry;
use PHPStan\ShouldNotHappenException;
use _PHPStan_c6b09fbdf\Symfony\Component\Console\Input\InputInterface;
use function array_merge;
use function count;
use function is_string;
use function memory_get_peak_usage;
use function microtime;
use function sprintf;
class AnalyseApplication
{
    /**
     * @var \PHPStan\Command\AnalyserRunner
     */
    private $analyserRunner;
    /**
     * @var \PHPStan\PhpDoc\StubValidator
     */
    private $stubValidator;
    /**
     * @var \PHPStan\Analyser\ResultCache\ResultCacheManagerFactory
     */
    private $resultCacheManagerFactory;
    /**
     * @var \PHPStan\Analyser\IgnoredErrorHelper
     */
    private $ignoredErrorHelper;
    /**
     * @var int
     */
    private $internalErrorsCountLimit;
    /**
     * @var \PHPStan\PhpDoc\StubFilesProvider
     */
    private $stubFilesProvider;
    /**
     * @var RuleRegistry
     */
    private $ruleRegistry;
    /**
     * @var \PHPStan\Analyser\ScopeFactory
     */
    private $scopeFactory;
    /**
     * @var \PHPStan\Analyser\RuleErrorTransformer
     */
    private $ruleErrorTransformer;
    public function __construct(\PHPStan\Command\AnalyserRunner $analyserRunner, StubValidator $stubValidator, ResultCacheManagerFactory $resultCacheManagerFactory, IgnoredErrorHelper $ignoredErrorHelper, int $internalErrorsCountLimit, StubFilesProvider $stubFilesProvider, RuleRegistry $ruleRegistry, ScopeFactory $scopeFactory, RuleErrorTransformer $ruleErrorTransformer)
    {
        $this->analyserRunner = $analyserRunner;
        $this->stubValidator = $stubValidator;
        $this->resultCacheManagerFactory = $resultCacheManagerFactory;
        $this->ignoredErrorHelper = $ignoredErrorHelper;
        $this->internalErrorsCountLimit = $internalErrorsCountLimit;
        $this->stubFilesProvider = $stubFilesProvider;
        $this->ruleRegistry = $ruleRegistry;
        $this->scopeFactory = $scopeFactory;
        $this->ruleErrorTransformer = $ruleErrorTransformer;
    }
    /**
     * @param string[] $files
     * @param mixed[]|null $projectConfigArray
     */
    public function analyse(array $files, bool $onlyFiles, \PHPStan\Command\Output $stdOutput, \PHPStan\Command\Output $errorOutput, bool $defaultLevelUsed, bool $debug, ?string $projectConfigFile, ?array $projectConfigArray, InputInterface $input) : \PHPStan\Command\AnalysisResult
    {
        $isResultCacheUsed = \false;
        $resultCacheManager = $this->resultCacheManagerFactory->create();
        $ignoredErrorHelperResult = $this->ignoredErrorHelper->initialize();
        if (count($ignoredErrorHelperResult->getErrors()) > 0) {
            $errors = $ignoredErrorHelperResult->getErrors();
            $internalErrors = [];
            $collectedData = [];
            $savedResultCache = \false;
            $memoryUsageBytes = memory_get_peak_usage(\true);
            if ($errorOutput->isDebug()) {
                $errorOutput->writeLineFormatted('Result cache was not saved because of ignoredErrorHelperResult errors.');
            }
        } else {
            $resultCache = $resultCacheManager->restore($files, $debug, $onlyFiles, $projectConfigArray, $errorOutput);
            $intermediateAnalyserResult = $this->runAnalyser($resultCache->getFilesToAnalyse(), $files, $debug, $projectConfigFile, $stdOutput, $errorOutput, $input);
            $projectStubFiles = $this->stubFilesProvider->getProjectStubFiles();
            if ($resultCache->isFullAnalysis() && count($projectStubFiles) !== 0) {
                $stubErrors = $this->stubValidator->validate($projectStubFiles, $debug);
                $intermediateAnalyserResult = new AnalyserResult(array_merge($intermediateAnalyserResult->getUnorderedErrors(), $stubErrors), $intermediateAnalyserResult->getInternalErrors(), $intermediateAnalyserResult->getCollectedData(), $intermediateAnalyserResult->getDependencies(), $intermediateAnalyserResult->getExportedNodes(), $intermediateAnalyserResult->hasReachedInternalErrorsCountLimit(), $intermediateAnalyserResult->getPeakMemoryUsageBytes());
            }
            $resultCacheResult = $resultCacheManager->process($intermediateAnalyserResult, $resultCache, $errorOutput, $onlyFiles, \true);
            $analyserResult = $resultCacheResult->getAnalyserResult();
            $internalErrors = $analyserResult->getInternalErrors();
            $errors = $analyserResult->getErrors();
            $hasInternalErrors = count($internalErrors) > 0 || $analyserResult->hasReachedInternalErrorsCountLimit();
            $memoryUsageBytes = $analyserResult->getPeakMemoryUsageBytes();
            $isResultCacheUsed = !$resultCache->isFullAnalysis();
            if (!$hasInternalErrors) {
                foreach ($this->getCollectedDataErrors($analyserResult->getCollectedData(), $onlyFiles) as $error) {
                    $errors[] = $error;
                }
            }
            $errors = $ignoredErrorHelperResult->process($errors, $onlyFiles, $files, $hasInternalErrors);
            $collectedData = $analyserResult->getCollectedData();
            $savedResultCache = $resultCacheResult->isSaved();
            if ($analyserResult->hasReachedInternalErrorsCountLimit()) {
                $errors[] = sprintf('Reached internal errors count limit of %d, exiting...', $this->internalErrorsCountLimit);
            }
            $errors = array_merge($errors, $internalErrors);
        }
        $fileSpecificErrors = [];
        $notFileSpecificErrors = [];
        foreach ($errors as $error) {
            if (is_string($error)) {
                $notFileSpecificErrors[] = $error;
                continue;
            }
            $fileSpecificErrors[] = $error;
        }
        return new \PHPStan\Command\AnalysisResult($fileSpecificErrors, $notFileSpecificErrors, $internalErrors, [], $collectedData, $defaultLevelUsed, $projectConfigFile, $savedResultCache, $memoryUsageBytes, $isResultCacheUsed);
    }
    /**
     * @param CollectedData[] $collectedData
     * @return Error[]
     */
    private function getCollectedDataErrors(array $collectedData, bool $onlyFiles) : array
    {
        $nodeType = CollectedDataNode::class;
        $node = new CollectedDataNode($collectedData, $onlyFiles);
        $file = 'N/A';
        $scope = $this->scopeFactory->create(ScopeContext::create($file));
        $errors = [];
        foreach ($this->ruleRegistry->getRules($nodeType) as $rule) {
            try {
                $ruleErrors = $rule->processNode($node, $scope);
            } catch (AnalysedCodeException $e) {
                $errors[] = new Error($e->getMessage(), $file, $node->getLine(), $e, null, null, $e->getTip());
                continue;
            } catch (IdentifierNotFound $e) {
                $errors[] = new Error(sprintf('Reflection error: %s not found.', $e->getIdentifier()->getName()), $file, $node->getLine(), $e, null, null, 'Learn more at https://phpstan.org/user-guide/discovering-symbols');
                continue;
            } catch (UnableToCompileNode|CircularReference $e) {
                $errors[] = new Error(sprintf('Reflection error: %s', $e->getMessage()), $file, $node->getLine(), $e);
                continue;
            }
            foreach ($ruleErrors as $ruleError) {
                $errors[] = $this->ruleErrorTransformer->transform($ruleError, $scope, $nodeType, $node->getLine());
            }
        }
        return $errors;
    }
    /**
     * @param string[] $files
     * @param string[] $allAnalysedFiles
     */
    private function runAnalyser(array $files, array $allAnalysedFiles, bool $debug, ?string $projectConfigFile, \PHPStan\Command\Output $stdOutput, \PHPStan\Command\Output $errorOutput, InputInterface $input) : AnalyserResult
    {
        $filesCount = count($files);
        $allAnalysedFilesCount = count($allAnalysedFiles);
        if ($filesCount === 0) {
            $errorOutput->getStyle()->progressStart($allAnalysedFilesCount);
            $errorOutput->getStyle()->progressAdvance($allAnalysedFilesCount);
            $errorOutput->getStyle()->progressFinish();
            return new AnalyserResult([], [], [], [], [], \false, memory_get_peak_usage(\true));
        }
        if (!$debug) {
            $preFileCallback = null;
            $postFileCallback = static function (int $step) use($errorOutput) : void {
                $errorOutput->getStyle()->progressAdvance($step);
            };
            $errorOutput->getStyle()->progressStart($allAnalysedFilesCount);
            $errorOutput->getStyle()->progressAdvance($allAnalysedFilesCount - $filesCount);
        } else {
            $startTime = null;
            $preFileCallback = static function (string $file) use($stdOutput, &$startTime) : void {
                $stdOutput->writeLineFormatted($file);
                $startTime = microtime(\true);
            };
            $postFileCallback = null;
            if ($stdOutput->isDebug()) {
                $previousMemory = memory_get_peak_usage(\true);
                $postFileCallback = static function () use($stdOutput, &$previousMemory, &$startTime) : void {
                    if ($startTime === null) {
                        throw new ShouldNotHappenException();
                    }
                    $currentTotalMemory = memory_get_peak_usage(\true);
                    $elapsedTime = microtime(\true) - $startTime;
                    $stdOutput->writeLineFormatted(sprintf('--- consumed %s, total %s, took %.2f s', BytesHelper::bytes($currentTotalMemory - $previousMemory), BytesHelper::bytes($currentTotalMemory), $elapsedTime));
                    $previousMemory = $currentTotalMemory;
                };
            }
        }
        $analyserResult = $this->analyserRunner->runAnalyser($files, $allAnalysedFiles, $preFileCallback, $postFileCallback, $debug, \true, $projectConfigFile, $input);
        if (!$debug) {
            $errorOutput->getStyle()->progressFinish();
        }
        return $analyserResult;
    }
}
