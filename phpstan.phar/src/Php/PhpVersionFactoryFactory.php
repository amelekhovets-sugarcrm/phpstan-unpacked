<?php

declare (strict_types=1);
namespace PHPStan\Php;

use _PHPStan_c6b09fbdf\Nette\Utils\Json;
use _PHPStan_c6b09fbdf\Nette\Utils\JsonException;
use PHPStan\File\CouldNotReadFileException;
use PHPStan\File\FileReader;
use function count;
use function end;
use function is_file;
use function is_string;
class PhpVersionFactoryFactory
{
    /**
     * @var int|null
     */
    private $versionId;
    /**
     * @var string[]
     */
    private $composerAutoloaderProjectPaths;
    /**
     * @param string[] $composerAutoloaderProjectPaths
     */
    public function __construct(?int $versionId, array $composerAutoloaderProjectPaths)
    {
        $this->versionId = $versionId;
        $this->composerAutoloaderProjectPaths = $composerAutoloaderProjectPaths;
    }
    public function create() : \PHPStan\Php\PhpVersionFactory
    {
        $composerPhpVersion = null;
        if (count($this->composerAutoloaderProjectPaths) > 0) {
            $composerJsonPath = end($this->composerAutoloaderProjectPaths) . '/composer.json';
            if (is_file($composerJsonPath)) {
                try {
                    $composerJsonContents = FileReader::read($composerJsonPath);
                    $composer = Json::decode($composerJsonContents, Json::FORCE_ARRAY);
                    $platformVersion = $composer['config']['platform']['php'] ?? null;
                    if (is_string($platformVersion)) {
                        $composerPhpVersion = $platformVersion;
                    }
                } catch (CouldNotReadFileException|JsonException $exception) {
                    // pass
                }
            }
        }
        return new \PHPStan\Php\PhpVersionFactory($this->versionId, $composerPhpVersion);
    }
}
