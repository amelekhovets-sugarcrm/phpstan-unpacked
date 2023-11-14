<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Type;
/** @api */
class ParamOutTag implements \PHPStan\PhpDoc\Tag\TypedTag
{
    /**
     * @var \PHPStan\Type\Type
     */
    private $type;
    public function __construct(Type $type)
    {
        $this->type = $type;
    }
    public function getType() : Type
    {
        return $this->type;
    }
    /**
     * @return self
     */
    public function withType(Type $type) : \PHPStan\PhpDoc\Tag\TypedTag
    {
        return new self($type);
    }
}
