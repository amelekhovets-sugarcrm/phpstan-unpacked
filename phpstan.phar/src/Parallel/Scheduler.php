<?php

declare (strict_types=1);
namespace PHPStan\Parallel;

use function array_chunk;
use function count;
use function floor;
use function max;
use function min;
class Scheduler
{
    /**
     * @var positive-int
     */
    private $jobSize;
    /**
     * @var positive-int
     */
    private $maximumNumberOfProcesses;
    /**
     * @var positive-int
     */
    private $minimumNumberOfJobsPerProcess;
    /**
     * @param positive-int $jobSize
     * @param positive-int $maximumNumberOfProcesses
     * @param positive-int $minimumNumberOfJobsPerProcess
     */
    public function __construct(int $jobSize, int $maximumNumberOfProcesses, int $minimumNumberOfJobsPerProcess)
    {
        $this->jobSize = $jobSize;
        $this->maximumNumberOfProcesses = $maximumNumberOfProcesses;
        $this->minimumNumberOfJobsPerProcess = $minimumNumberOfJobsPerProcess;
    }
    /**
     * @param array<string> $files
     */
    public function scheduleWork(int $cpuCores, array $files) : \PHPStan\Parallel\Schedule
    {
        $jobs = array_chunk($files, $this->jobSize);
        $numberOfProcesses = min(max((int) floor(count($jobs) / $this->minimumNumberOfJobsPerProcess), 1), $cpuCores);
        return new \PHPStan\Parallel\Schedule(min($numberOfProcesses, $this->maximumNumberOfProcesses), $jobs);
    }
}
