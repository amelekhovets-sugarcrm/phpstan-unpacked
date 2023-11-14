<?php

declare (strict_types=1);
namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use function array_key_exists;
class OptimizedDirectorySourceLocatorRepository
{
    /**
     * @var \PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectorySourceLocatorFactory
     */
    private $factory;
    /** @var array<string, NewOptimizedDirectorySourceLocator> */
    private $locators = [];
    public function __construct(\PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectorySourceLocatorFactory $factory)
    {
        $this->factory = $factory;
    }
    public function getOrCreate(string $directory) : \PHPStan\Reflection\BetterReflection\SourceLocator\NewOptimizedDirectorySourceLocator
    {
        if (array_key_exists($directory, $this->locators)) {
            return $this->locators[$directory];
        }
        $this->locators[$directory] = $this->factory->createByDirectory($directory);
        return $this->locators[$directory];
    }
}
