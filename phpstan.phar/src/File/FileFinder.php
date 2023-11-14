<?php

declare (strict_types=1);
namespace PHPStan\File;

use _PHPStan_c6b09fbdf\Symfony\Component\Finder\Finder;
use function array_filter;
use function array_unique;
use function array_values;
use function file_exists;
use function implode;
use function is_file;
class FileFinder
{
    /**
     * @var \PHPStan\File\FileExcluder
     */
    private $fileExcluder;
    /**
     * @var \PHPStan\File\FileHelper
     */
    private $fileHelper;
    /**
     * @var string[]
     */
    private $fileExtensions;
    /**
     * @param string[] $fileExtensions
     */
    public function __construct(\PHPStan\File\FileExcluder $fileExcluder, \PHPStan\File\FileHelper $fileHelper, array $fileExtensions)
    {
        $this->fileExcluder = $fileExcluder;
        $this->fileHelper = $fileHelper;
        $this->fileExtensions = $fileExtensions;
    }
    /**
     * @param string[] $paths
     */
    public function findFiles(array $paths) : \PHPStan\File\FileFinderResult
    {
        $onlyFiles = \true;
        $files = [];
        foreach ($paths as $path) {
            if (is_file($path)) {
                $files[] = $this->fileHelper->normalizePath($path);
            } elseif (!file_exists($path)) {
                throw new \PHPStan\File\PathNotFoundException($path);
            } else {
                $finder = new Finder();
                $finder->followLinks();
                foreach ($finder->files()->name('*.{' . implode(',', $this->fileExtensions) . '}')->in($path) as $fileInfo) {
                    $files[] = $this->fileHelper->normalizePath($fileInfo->getPathname());
                    $onlyFiles = \false;
                }
            }
        }
        $files = array_values(array_unique(array_filter($files, function (string $file) : bool {
            return !$this->fileExcluder->isExcludedFromAnalysing($file);
        })));
        return new \PHPStan\File\FileFinderResult($files, $onlyFiles);
    }
}
