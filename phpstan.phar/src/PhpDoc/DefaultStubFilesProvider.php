<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc;

use PHPStan\DependencyInjection\Container;
use PHPStan\Internal\ComposerHelper;
use function array_filter;
use function array_values;
use function strpos;
use function strtr;
class DefaultStubFilesProvider implements \PHPStan\PhpDoc\StubFilesProvider
{
    /**
     * @var \PHPStan\DependencyInjection\Container
     */
    private $container;
    /**
     * @var string[]
     */
    private $stubFiles;
    /**
     * @var string
     */
    private $currentWorkingDirectory;
    /** @var string[]|null */
    private $cachedFiles;
    /** @var string[]|null */
    private $cachedProjectFiles;
    /**
     * @param string[] $stubFiles
     */
    public function __construct(Container $container, array $stubFiles, string $currentWorkingDirectory)
    {
        $this->container = $container;
        $this->stubFiles = $stubFiles;
        $this->currentWorkingDirectory = $currentWorkingDirectory;
    }
    public function getStubFiles() : array
    {
        if ($this->cachedFiles !== null) {
            return $this->cachedFiles;
        }
        $files = $this->stubFiles;
        $extensions = $this->container->getServicesByTag(\PHPStan\PhpDoc\StubFilesExtension::EXTENSION_TAG);
        foreach ($extensions as $extension) {
            foreach ($extension->getFiles() as $extensionFile) {
                $files[] = $extensionFile;
            }
        }
        return $this->cachedFiles = $files;
    }
    public function getProjectStubFiles() : array
    {
        if ($this->cachedProjectFiles !== null) {
            return $this->cachedProjectFiles;
        }
        $composerConfig = ComposerHelper::getComposerConfig($this->currentWorkingDirectory);
        if ($composerConfig === null) {
            return $this->getStubFiles();
        }
        $vendorDir = ComposerHelper::getVendorDirFromComposerConfig($this->currentWorkingDirectory, $composerConfig);
        $vendorDir = strtr($vendorDir, '\\', '/');
        return $this->cachedProjectFiles = array_values(array_filter($this->getStubFiles(), static function (string $file) use($vendorDir) : bool {
            return strpos(strtr($file, '\\', '/'), $vendorDir) === \false;
        }));
    }
}
