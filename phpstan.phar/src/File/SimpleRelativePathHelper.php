<?php

declare (strict_types=1);
namespace PHPStan\File;

use function str_replace;
use function strlen;
use function strpos;
use function substr;
class SimpleRelativePathHelper implements \PHPStan\File\RelativePathHelper
{
    /**
     * @var string
     */
    private $currentWorkingDirectory;
    public function __construct(string $currentWorkingDirectory)
    {
        $this->currentWorkingDirectory = $currentWorkingDirectory;
    }
    public function getRelativePath(string $filename) : string
    {
        if ($this->currentWorkingDirectory !== '' && strpos($filename, $this->currentWorkingDirectory) === 0) {
            return str_replace('\\', '/', substr($filename, strlen($this->currentWorkingDirectory) + 1));
        }
        return str_replace('\\', '/', $filename);
    }
}