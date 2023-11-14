<?php

declare (strict_types=1);
namespace PHPStan\Type\Generic;

use PHPStan\Type\ObjectShapeType;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
/** @api */
final class TemplateObjectShapeType extends ObjectShapeType implements \PHPStan\Type\Generic\TemplateType
{
    /** @use TemplateTypeTrait<ObjectShapeType> */
    use \PHPStan\Type\Generic\TemplateTypeTrait;
    use UndecidedComparisonCompoundTypeTrait;
    public function __construct(\PHPStan\Type\Generic\TemplateTypeScope $scope, \PHPStan\Type\Generic\TemplateTypeStrategy $templateTypeStrategy, \PHPStan\Type\Generic\TemplateTypeVariance $templateTypeVariance, string $name, ObjectShapeType $bound)
    {
        parent::__construct($bound->getProperties(), $bound->getOptionalProperties());
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
