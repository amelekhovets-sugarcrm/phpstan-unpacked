<?php

declare (strict_types=1);
namespace PHPStan\Type\Generic;

use PHPStan\Type\IntersectionType;
/** @api */
final class TemplateIntersectionType extends IntersectionType implements \PHPStan\Type\Generic\TemplateType
{
    /** @use TemplateTypeTrait<IntersectionType> */
    use \PHPStan\Type\Generic\TemplateTypeTrait;
    public function __construct(\PHPStan\Type\Generic\TemplateTypeScope $scope, \PHPStan\Type\Generic\TemplateTypeStrategy $templateTypeStrategy, \PHPStan\Type\Generic\TemplateTypeVariance $templateTypeVariance, string $name, IntersectionType $bound)
    {
        parent::__construct($bound->getTypes());
        $this->scope = $scope;
        $this->strategy = $templateTypeStrategy;
        $this->variance = $templateTypeVariance;
        $this->name = $name;
        $this->bound = $bound;
    }
}
