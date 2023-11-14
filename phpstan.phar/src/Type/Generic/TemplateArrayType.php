<?php

declare (strict_types=1);
namespace PHPStan\Type\Generic;

use PHPStan\Type\ArrayType;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
/** @api */
final class TemplateArrayType extends ArrayType implements \PHPStan\Type\Generic\TemplateType
{
    /** @use TemplateTypeTrait<ArrayType> */
    use \PHPStan\Type\Generic\TemplateTypeTrait;
    use UndecidedComparisonCompoundTypeTrait;
    public function __construct(\PHPStan\Type\Generic\TemplateTypeScope $scope, \PHPStan\Type\Generic\TemplateTypeStrategy $templateTypeStrategy, \PHPStan\Type\Generic\TemplateTypeVariance $templateTypeVariance, string $name, ArrayType $bound)
    {
        parent::__construct($bound->getKeyType(), $bound->getItemType());
        $this->scope = $scope;
        $this->strategy = $templateTypeStrategy;
        $this->variance = $templateTypeVariance;
        $this->name = $name;
        $this->bound = $bound;
    }
    protected function shouldGeneralizeInferredType() : bool
    {
        return \false;
    }
}
