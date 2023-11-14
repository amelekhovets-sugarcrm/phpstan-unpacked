<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Type;
/** @api */
class TemplateTag
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var \PHPStan\Type\Type
     */
    private $bound;
    /**
     * @var \PHPStan\Type\Generic\TemplateTypeVariance
     */
    private $variance;
    public function __construct(string $name, Type $bound, TemplateTypeVariance $variance)
    {
        $this->name = $name;
        $this->bound = $bound;
        $this->variance = $variance;
    }
    public function getName() : string
    {
        return $this->name;
    }
    public function getBound() : Type
    {
        return $this->bound;
    }
    public function getVariance() : TemplateTypeVariance
    {
        return $this->variance;
    }
}
