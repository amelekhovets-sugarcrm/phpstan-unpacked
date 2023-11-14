<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc;

class CountableStubFilesExtension implements \PHPStan\PhpDoc\StubFilesExtension
{
    /**
     * @var bool
     */
    private $bleedingEdge;
    public function __construct(bool $bleedingEdge)
    {
        $this->bleedingEdge = $bleedingEdge;
    }
    public function getFiles() : array
    {
        if ($this->bleedingEdge) {
            return [__DIR__ . '/../../stubs/bleedingEdge/Countable.stub'];
        }
        return [__DIR__ . '/../../stubs/Countable.stub'];
    }
}
