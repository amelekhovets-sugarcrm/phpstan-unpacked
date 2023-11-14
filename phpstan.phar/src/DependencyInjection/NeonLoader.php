<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection;

use _PHPStan_c6b09fbdf\Nette\DI\Config\Loader;
use PHPStan\File\FileHelper;
class NeonLoader extends Loader
{
    /**
     * @var \PHPStan\File\FileHelper
     */
    private $fileHelper;
    /**
     * @var string|null
     */
    private $generateBaselineFile;
    public function __construct(FileHelper $fileHelper, ?string $generateBaselineFile)
    {
        $this->fileHelper = $fileHelper;
        $this->generateBaselineFile = $generateBaselineFile;
    }
    /**
     * @return mixed[]
     */
    public function load(string $file, ?bool $merge = \true) : array
    {
        if ($this->generateBaselineFile === null) {
            return parent::load($file, $merge);
        }
        $normalizedFile = $this->fileHelper->normalizePath($file);
        if ($this->generateBaselineFile === $normalizedFile) {
            return [];
        }
        return parent::load($file, $merge);
    }
}
