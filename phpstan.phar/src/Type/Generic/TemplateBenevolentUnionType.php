<?php

declare (strict_types=1);
namespace PHPStan\Type\Generic;

use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Type;
/** @api */
final class TemplateBenevolentUnionType extends BenevolentUnionType implements \PHPStan\Type\Generic\TemplateType
{
    /** @use TemplateTypeTrait<BenevolentUnionType> */
    use \PHPStan\Type\Generic\TemplateTypeTrait;
    public function __construct(\PHPStan\Type\Generic\TemplateTypeScope $scope, \PHPStan\Type\Generic\TemplateTypeStrategy $templateTypeStrategy, \PHPStan\Type\Generic\TemplateTypeVariance $templateTypeVariance, string $name, BenevolentUnionType $bound)
    {
        parent::__construct($bound->getTypes());
        $this->scope = $scope;
        $this->strategy = $templateTypeStrategy;
        $this->variance = $templateTypeVariance;
        $this->name = $name;
        $this->bound = $bound;
    }
    /** @param Type[] $types */
    public function withTypes(array $types) : self
    {
        return new self($this->scope, $this->strategy, $this->variance, $this->name, new BenevolentUnionType($types));
    }
}
