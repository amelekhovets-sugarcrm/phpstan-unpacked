<?php

declare (strict_types=1);
namespace PHPStan\Type\Generic;

use PHPStan\Type\IntegerType;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
/** @api */
final class TemplateIntegerType extends IntegerType implements \PHPStan\Type\Generic\TemplateType
{
    /** @use TemplateTypeTrait<IntegerType> */
    use \PHPStan\Type\Generic\TemplateTypeTrait;
    use UndecidedComparisonCompoundTypeTrait;
    public function __construct(\PHPStan\Type\Generic\TemplateTypeScope $scope, \PHPStan\Type\Generic\TemplateTypeStrategy $templateTypeStrategy, \PHPStan\Type\Generic\TemplateTypeVariance $templateTypeVariance, string $name, IntegerType $bound)
    {
        parent::__construct();
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
