<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection;

use _PHPStan_c6b09fbdf\Nette\DI\Config\Loader;
use _PHPStan_c6b09fbdf\Nette\DI\Container as OriginalNetteContainer;
use _PHPStan_c6b09fbdf\Nette\DI\ContainerLoader;
use PHPStan\File\CouldNotReadFileException;
use function array_keys;
use function error_reporting;
use function restore_error_handler;
use function set_error_handler;
use function sha1_file;
use const E_USER_DEPRECATED;
use const PHP_RELEASE_VERSION;
use const PHP_VERSION_ID;
class Configurator extends \_PHPStan_c6b09fbdf\Nette\Bootstrap\Configurator
{
    /**
     * @var \PHPStan\DependencyInjection\LoaderFactory
     */
    private $loaderFactory;
    /** @var string[] */
    private $allConfigFiles = [];
    public function __construct(\PHPStan\DependencyInjection\LoaderFactory $loaderFactory)
    {
        $this->loaderFactory = $loaderFactory;
        parent::__construct();
    }
    protected function createLoader() : Loader
    {
        return $this->loaderFactory->createLoader();
    }
    /**
     * @param string[] $allConfigFiles
     */
    public function setAllConfigFiles(array $allConfigFiles) : void
    {
        $this->allConfigFiles = $allConfigFiles;
    }
    /**
     * @return mixed[]
     */
    protected function getDefaultParameters() : array
    {
        return [];
    }
    public function getContainerCacheDirectory() : string
    {
        return $this->getCacheDirectory() . '/nette.configurator';
    }
    public function loadContainer() : string
    {
        $loader = new ContainerLoader($this->getContainerCacheDirectory(), $this->staticParameters['debugMode']);
        return $loader->load([$this, 'generateContainer'], [$this->staticParameters, array_keys($this->dynamicParameters), $this->configs, PHP_VERSION_ID - PHP_RELEASE_VERSION, \PHPStan\DependencyInjection\NeonAdapter::CACHE_KEY, $this->getAllConfigFilesHashes()]);
    }
    public function createContainer(bool $initialize = \true) : OriginalNetteContainer
    {
        set_error_handler(static function (int $errno) : bool {
            if ((error_reporting() & $errno) === 0) {
                // silence @ operator
                return \true;
            }
            return $errno === E_USER_DEPRECATED;
        });
        try {
            $container = parent::createContainer($initialize);
        } finally {
            restore_error_handler();
        }
        return $container;
    }
    /**
     * @return string[]
     */
    private function getAllConfigFilesHashes() : array
    {
        $hashes = [];
        foreach ($this->allConfigFiles as $file) {
            $hash = sha1_file($file);
            if ($hash === \false) {
                throw new CouldNotReadFileException($file);
            }
            $hashes[$file] = $hash;
        }
        return $hashes;
    }
}
