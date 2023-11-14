<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Type;
/** @api */
class PropertyTag
{
    /**
     * @var \PHPStan\Type\Type
     */
    private $type;
    /**
     * @var \PHPStan\Type\Type|null
     */
    private $readableType;
    /**
     * @var \PHPStan\Type\Type|null
     */
    private $writableType;
    public function __construct(Type $type, ?Type $readableType, ?Type $writableType)
    {
        $this->type = $type;
        $this->readableType = $readableType;
        $this->writableType = $writableType;
    }
    /**
     * @deprecated Use getReadableType() / getWritableType()
     */
    public function getType() : Type
    {
        return $this->type;
    }
    public function getReadableType() : ?Type
    {
        return $this->readableType;
    }
    public function getWritableType() : ?Type
    {
        return $this->writableType;
    }
    /**
     * @phpstan-assert-if-true !null $this->getReadableType()
     */
    public function isReadable() : bool
    {
        return $this->readableType !== null;
    }
    /**
     * @phpstan-assert-if-true !null $this->getWritableType()
     */
    public function isWritable() : bool
    {
        return $this->writableType !== null;
    }
}
