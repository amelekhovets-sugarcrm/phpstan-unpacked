<?php

declare (strict_types=1);
namespace PHPStan\Parallel;

use Closure;
use _PHPStan_c6b09fbdf\Clue\React\NDJson\Decoder;
use _PHPStan_c6b09fbdf\Clue\React\NDJson\Encoder;
use _PHPStan_c6b09fbdf\Nette\Utils\Random;
use PHPStan\Analyser\AnalyserResult;
use PHPStan\Analyser\Error;
use PHPStan\Collectors\CollectedData;
use PHPStan\Dependency\RootExportedNode;
use PHPStan\Process\ProcessHelper;
use _PHPStan_c6b09fbdf\React\EventLoop\StreamSelectLoop;
use _PHPStan_c6b09fbdf\React\Socket\ConnectionInterface;
use _PHPStan_c6b09fbdf\React\Socket\TcpServer;
use _PHPStan_c6b09fbdf\Symfony\Component\Console\Input\InputInterface;
use Throwable;
use function array_map;
use function array_pop;
use function array_reverse;
use function array_sum;
use function count;
use function defined;
use function is_string;
use function max;
use function memory_get_usage;
use function parse_url;
use function sprintf;
use const PHP_URL_PORT;
class ParallelAnalyser
{
    /**
     * @var int
     */
    private $internalErrorsCountLimit;
    /**
     * @var int
     */
    private $decoderBufferSize;
    private const DEFAULT_TIMEOUT = 600.0;
    /**
     * @var float
     */
    private $processTimeout;
    /**
     * @var \PHPStan\Parallel\ProcessPool
     */
    private $processPool;
    public function __construct(int $internalErrorsCountLimit, float $processTimeout, int $decoderBufferSize)
    {
        $this->internalErrorsCountLimit = $internalErrorsCountLimit;
        $this->decoderBufferSize = $decoderBufferSize;
        $this->processTimeout = max($processTimeout, self::DEFAULT_TIMEOUT);
    }
    /**
     * @param Closure(int ): void|null $postFileCallback
     */
    public function analyse(\PHPStan\Parallel\Schedule $schedule, string $mainScript, ?Closure $postFileCallback, ?string $projectConfigFile, InputInterface $input) : AnalyserResult
    {
        $jobs = array_reverse($schedule->getJobs());
        $loop = new StreamSelectLoop();
        $numberOfProcesses = $schedule->getNumberOfProcesses();
        $someChildEnded = \false;
        $errors = [];
        $peakMemoryUsages = [];
        $internalErrors = [];
        $collectedData = [];
        $server = new TcpServer('127.0.0.1:0', $loop);
        $this->processPool = new \PHPStan\Parallel\ProcessPool($server);
        $server->on('connection', function (ConnectionInterface $connection) use(&$jobs) : void {
            // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly
            $jsonInvalidUtf8Ignore = defined('JSON_INVALID_UTF8_IGNORE') ? \JSON_INVALID_UTF8_IGNORE : 0;
            // phpcs:enable
            $decoder = new Decoder($connection, \true, 512, $jsonInvalidUtf8Ignore, $this->decoderBufferSize);
            $encoder = new Encoder($connection, $jsonInvalidUtf8Ignore);
            $decoder->on('data', function (array $data) use(&$jobs, $decoder, $encoder) : void {
                if ($data['action'] !== 'hello') {
                    return;
                }
                $identifier = $data['identifier'];
                $process = $this->processPool->getProcess($identifier);
                $process->bindConnection($decoder, $encoder);
                if (count($jobs) === 0) {
                    $this->processPool->tryQuitProcess($identifier);
                    return;
                }
                $job = array_pop($jobs);
                $process->request(['action' => 'analyse', 'files' => $job]);
            });
        });
        /** @var string $serverAddress */
        $serverAddress = $server->getAddress();
        /** @var int<0, 65535> $serverPort */
        $serverPort = parse_url($serverAddress, PHP_URL_PORT);
        $internalErrorsCount = 0;
        $reachedInternalErrorsCountLimit = \false;
        $handleError = function (Throwable $error) use(&$internalErrors, &$internalErrorsCount, &$reachedInternalErrorsCountLimit) : void {
            $internalErrors[] = sprintf('Internal error: ' . $error->getMessage());
            $internalErrorsCount++;
            $reachedInternalErrorsCountLimit = \true;
            $this->processPool->quitAll();
        };
        $dependencies = [];
        $exportedNodes = [];
        for ($i = 0; $i < $numberOfProcesses; $i++) {
            if (count($jobs) === 0) {
                break;
            }
            $processIdentifier = Random::generate();
            $commandOptions = ['--port', (string) $serverPort, '--identifier', $processIdentifier];
            $process = new \PHPStan\Parallel\Process(ProcessHelper::getWorkerCommand($mainScript, 'worker', $projectConfigFile, $commandOptions, $input), $loop, $this->processTimeout);
            $process->start(function (array $json) use($process, &$internalErrors, &$errors, &$collectedData, &$dependencies, &$exportedNodes, &$peakMemoryUsages, &$jobs, $postFileCallback, &$internalErrorsCount, &$reachedInternalErrorsCountLimit, $processIdentifier) : void {
                foreach ($json['errors'] as $jsonError) {
                    if (is_string($jsonError)) {
                        $internalErrors[] = sprintf('Internal error: %s', $jsonError);
                        continue;
                    }
                    $errors[] = Error::decode($jsonError);
                }
                foreach ($json['collectedData'] as $jsonData) {
                    $collectedData[] = CollectedData::decode($jsonData);
                }
                /**
                 * @var string $file
                 * @var array<string> $fileDependencies
                 */
                foreach ($json['dependencies'] as $file => $fileDependencies) {
                    $dependencies[$file] = $fileDependencies;
                }
                /**
                 * @var string $file
                 * @var array<mixed[]> $fileExportedNodes
                 */
                foreach ($json['exportedNodes'] as $file => $fileExportedNodes) {
                    if (count($fileExportedNodes) === 0) {
                        continue;
                    }
                    $exportedNodes[$file] = array_map(static function (array $node) : RootExportedNode {
                        $class = $node['type'];
                        return $class::decode($node['data']);
                    }, $fileExportedNodes);
                }
                if ($postFileCallback !== null) {
                    $postFileCallback($json['filesCount']);
                }
                if (!isset($peakMemoryUsages[$processIdentifier]) || $peakMemoryUsages[$processIdentifier] < $json['memoryUsage']) {
                    $peakMemoryUsages[$processIdentifier] = $json['memoryUsage'];
                }
                $internalErrorsCount += $json['internalErrorsCount'];
                if ($internalErrorsCount >= $this->internalErrorsCountLimit) {
                    $reachedInternalErrorsCountLimit = \true;
                    $this->processPool->quitAll();
                }
                if (count($jobs) === 0) {
                    $this->processPool->tryQuitProcess($processIdentifier);
                    return;
                }
                $job = array_pop($jobs);
                $process->request(['action' => 'analyse', 'files' => $job]);
            }, $handleError, function ($exitCode, string $output) use(&$someChildEnded, &$peakMemoryUsages, &$internalErrors, &$internalErrorsCount, $processIdentifier) : void {
                if ($someChildEnded === \false) {
                    $peakMemoryUsages['main'] = memory_get_usage(\true);
                }
                $someChildEnded = \true;
                $this->processPool->tryQuitProcess($processIdentifier);
                if ($exitCode === 0) {
                    return;
                }
                if ($exitCode === null) {
                    return;
                }
                $internalErrors[] = sprintf('Child process error (exit code %d): %s', $exitCode, $output);
                $internalErrorsCount++;
            });
            $this->processPool->attachProcess($processIdentifier, $process);
        }
        $loop->run();
        if (count($jobs) > 0 && $internalErrorsCount === 0) {
            $internalErrors[] = 'Some parallel worker jobs have not finished.';
            $internalErrorsCount++;
        }
        return new AnalyserResult($errors, $internalErrors, $collectedData, $internalErrorsCount === 0 ? $dependencies : null, $exportedNodes, $reachedInternalErrorsCountLimit, (int) array_sum($peakMemoryUsages));
    }
}
