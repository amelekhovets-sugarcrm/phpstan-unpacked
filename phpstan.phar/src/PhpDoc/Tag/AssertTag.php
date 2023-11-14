<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc\Tag;

use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Type;
/** @api */
final class AssertTag implements \PHPStan\PhpDoc\Tag\TypedTag
{
    /**
     * @var self::NULL|self::IF_TRUE|self::IF_FALSE
     */
    private $if;
    /**
     * @var \PHPStan\Type\Type
     */
    private $type;
    /**
     * @var \PHPStan\PhpDoc\Tag\AssertTagParameter
     */
    private $parameter;
    /**
     * @var bool
     */
    private $negated;
    /**
     * @var bool
     */
    private $equality;
    public const NULL = '';
    public const IF_TRUE = 'true';
    public const IF_FALSE = 'false';
    /**
     * @var \PHPStan\Type\Type|null
     */
    private $originalType;
    /**
     * @param self::NULL|self::IF_TRUE|self::IF_FALSE $if
     */
    public function __construct(string $if, Type $type, \PHPStan\PhpDoc\Tag\AssertTagParameter $parameter, bool $negated, bool $equality)
    {
        $this->if = $if;
        $this->type = $type;
        $this->parameter = $parameter;
        $this->negated = $negated;
        $this->equality = $equality;
    }
    /**
     * @return self::NULL|self::IF_TRUE|self::IF_FALSE
     */
    public function getIf() : string
    {
        return $this->if;
    }
    public function getType() : Type
    {
        return $this->type;
    }
    public function getOriginalType() : Type
    {
        return $this->originalType = $this->originalType ?? $this->type;
    }
    public function getParameter() : \PHPStan\PhpDoc\Tag\AssertTagParameter
    {
        return $this->parameter;
    }
    public function isNegated() : bool
    {
        return $this->negated;
    }
    public function isEquality() : bool
    {
        return $this->equality;
    }
    /**
     * @return static
     */
    public function withType(Type $type) : \PHPStan\PhpDoc\Tag\TypedTag
    {
        $tag = new self($this->if, $type, $this->parameter, $this->negated, $this->equality);
        $tag->originalType = $this->getOriginalType();
        return $tag;
    }
    public function withParameter(\PHPStan\PhpDoc\Tag\AssertTagParameter $parameter) : self
    {
        $tag = new self($this->if, $this->type, $parameter, $this->negated, $this->equality);
        $tag->originalType = $this->getOriginalType();
        return $tag;
    }
    public function negate() : self
    {
        if ($this->isEquality()) {
            throw new ShouldNotHappenException();
        }
        $tag = new self($this->if, $this->type, $this->parameter, !$this->negated, $this->equality);
        $tag->originalType = $this->getOriginalType();
        return $tag;
    }
}
