<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc;

use PhpParser\Parser;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorRepository;
class StubSourceLocatorFactory
{
    /**
     * @var \PhpParser\Parser
     */
    private $php8Parser;
    /**
     * @var \PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber
     */
    private $phpStormStubsSourceStubber;
    /**
     * @var \PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorRepository
     */
    private $optimizedSingleFileSourceLocatorRepository;
    /**
     * @var \PHPStan\PhpDoc\StubFilesProvider
     */
    private $stubFilesProvider;
    public function __construct(Parser $php8Parser, PhpStormStubsSourceStubber $phpStormStubsSourceStubber, OptimizedSingleFileSourceLocatorRepository $optimizedSingleFileSourceLocatorRepository, \PHPStan\PhpDoc\StubFilesProvider $stubFilesProvider)
    {
        $this->php8Parser = $php8Parser;
        $this->phpStormStubsSourceStubber = $phpStormStubsSourceStubber;
        $this->optimizedSingleFileSourceLocatorRepository = $optimizedSingleFileSourceLocatorRepository;
        $this->stubFilesProvider = $stubFilesProvider;
    }
    public function create() : SourceLocator
    {
        $locators = [];
        $astPhp8Locator = new Locator($this->php8Parser);
        foreach ($this->stubFilesProvider->getStubFiles() as $stubFile) {
            $locators[] = $this->optimizedSingleFileSourceLocatorRepository->getOrCreate($stubFile);
        }
        $locators[] = new PhpInternalSourceLocator($astPhp8Locator, $this->phpStormStubsSourceStubber);
        return new MemoizingSourceLocator(new AggregateSourceLocator($locators));
    }
}
