<?php

declare (strict_types=1);
namespace PHPStan\Reflection\BetterReflection\SourceStubber;

use PhpParser\Parser;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\Node\Printer\Printer;
use PHPStan\Php\PhpVersion;
class PhpStormStubsSourceStubberFactory
{
    /**
     * @var \PhpParser\Parser
     */
    private $phpParser;
    /**
     * @var \PHPStan\Node\Printer\Printer
     */
    private $printer;
    /**
     * @var \PHPStan\Php\PhpVersion
     */
    private $phpVersion;
    public function __construct(Parser $phpParser, Printer $printer, PhpVersion $phpVersion)
    {
        $this->phpParser = $phpParser;
        $this->printer = $printer;
        $this->phpVersion = $phpVersion;
    }
    public function create() : PhpStormStubsSourceStubber
    {
        return new PhpStormStubsSourceStubber($this->phpParser, $this->printer, $this->phpVersion->getVersionId());
    }
}
