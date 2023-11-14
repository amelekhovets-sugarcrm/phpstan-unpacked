<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Type;
/** @api */
class ParamTag implements \PHPStan\PhpDoc\Tag\TypedTag
{
    /**
     * @var \PHPStan\Type\Type
     */
    private $type;
    /**
     * @var bool
     */
    private $isVariadic;
    public function __construct(Type $type, bool $isVariadic)
    {
        $this->type = $type;
        $this->isVariadic = $isVariadic;
    }
    public function getType() : Type
    {
        return $this->type;
    }
    public function isVariadic() : bool
    {
        return $this->isVariadic;
    }
    /**
     * @return self
     */
    public function withType(Type $type) : \PHPStan\PhpDoc\Tag\TypedTag
    {
        return new self($type, $this->isVariadic);
    }
}
