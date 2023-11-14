<?php

declare (strict_types=1);
namespace PHPStan\Type\Generic;

use PHPStan\Type\UnionType;
/** @api */
final class TemplateUnionType extends UnionType implements \PHPStan\Type\Generic\TemplateType
{
    /** @use TemplateTypeTrait<UnionType> */
    use \PHPStan\Type\Generic\TemplateTypeTrait;
    public function __construct(\PHPStan\Type\Generic\TemplateTypeScope $scope, \PHPStan\Type\Generic\TemplateTypeStrategy $templateTypeStrategy, \PHPStan\Type\Generic\TemplateTypeVariance $templateTypeVariance, string $name, UnionType $bound)
    {
        parent::__construct($bound->getTypes());
        $this->scope = $scope;
        $this->strategy = $templateTypeStrategy;
        $this->variance = $templateTypeVariance;
        $this->name = $name;
        $this->bound = $bound;
    }
}
