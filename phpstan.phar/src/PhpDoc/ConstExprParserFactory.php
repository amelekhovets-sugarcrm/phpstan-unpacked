<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc;

use PHPStan\PhpDocParser\Parser\ConstExprParser;
class ConstExprParserFactory
{
    /**
     * @var bool
     */
    private $unescapeStrings;
    public function __construct(bool $unescapeStrings)
    {
        $this->unescapeStrings = $unescapeStrings;
    }
    public function create() : ConstExprParser
    {
        return new ConstExprParser($this->unescapeStrings, $this->unescapeStrings);
    }
}
