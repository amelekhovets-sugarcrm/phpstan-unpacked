<?php

declare (strict_types=1);
namespace PHPStan\Process;

use PHPStan\ShouldNotHappenException;
use _PHPStan_c6b09fbdf\React\ChildProcess\Process;
use _PHPStan_c6b09fbdf\React\EventLoop\LoopInterface;
use _PHPStan_c6b09fbdf\React\Promise\CancellablePromiseInterface;
use _PHPStan_c6b09fbdf\React\Promise\Deferred;
use _PHPStan_c6b09fbdf\React\Promise\ExtendedPromiseInterface;
use function fclose;
use function rewind;
use function stream_get_contents;
use function tmpfile;
class ProcessPromise
{
    /**
     * @var \React\EventLoop\LoopInterface
     */
    private $loop;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $command;
    /**
     * @var \React\Promise\Deferred
     */
    private $deferred;
    /**
     * @var \React\ChildProcess\Process|null
     */
    private $process;
    /**
     * @var bool
     */
    private $canceled = \false;
    public function __construct(LoopInterface $loop, string $name, string $command)
    {
        $this->loop = $loop;
        $this->name = $name;
        $this->command = $command;
        $this->deferred = new Deferred();
    }
    public function getName() : string
    {
        return $this->name;
    }
    /**
     * @return ExtendedPromiseInterface&CancellablePromiseInterface
     */
    public function run() : CancellablePromiseInterface
    {
        $tmpStdOutResource = tmpfile();
        if ($tmpStdOutResource === \false) {
            throw new ShouldNotHappenException('Failed creating temp file for stdout.');
        }
        $tmpStdErrResource = tmpfile();
        if ($tmpStdErrResource === \false) {
            throw new ShouldNotHappenException('Failed creating temp file for stderr.');
        }
        $this->process = new Process($this->command, null, null, [1 => $tmpStdOutResource, 2 => $tmpStdErrResource]);
        $this->process->start($this->loop);
        $this->process->on('exit', function ($exitCode) use($tmpStdOutResource, $tmpStdErrResource) : void {
            if ($this->canceled) {
                fclose($tmpStdOutResource);
                fclose($tmpStdErrResource);
                return;
            }
            rewind($tmpStdOutResource);
            $stdOut = stream_get_contents($tmpStdOutResource);
            fclose($tmpStdOutResource);
            rewind($tmpStdErrResource);
            $stdErr = stream_get_contents($tmpStdErrResource);
            fclose($tmpStdErrResource);
            if ($exitCode === null) {
                $this->deferred->reject(new \PHPStan\Process\ProcessCrashedException($stdOut . $stdErr));
                return;
            }
            if ($exitCode === 0) {
                $this->deferred->resolve($stdOut);
                return;
            }
            $this->deferred->reject(new \PHPStan\Process\ProcessCrashedException($stdOut . $stdErr));
        });
        /** @var ExtendedPromiseInterface&CancellablePromiseInterface */
        return $this->deferred->promise();
    }
    public function cancel() : void
    {
        if ($this->process === null) {
            throw new ShouldNotHappenException('Cancelling process before running');
        }
        $this->canceled = \true;
        $this->process->terminate();
        $this->deferred->reject(new \PHPStan\Process\ProcessCanceledException());
    }
}
