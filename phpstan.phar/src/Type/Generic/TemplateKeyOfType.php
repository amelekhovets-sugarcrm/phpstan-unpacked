<?php

declare (strict_types=1);
namespace PHPStan\Type\Generic;

use PHPStan\Type\KeyOfType;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use PHPStan\Type\Type;
/** @api */
final class TemplateKeyOfType extends KeyOfType implements \PHPStan\Type\Generic\TemplateType
{
    /** @use TemplateTypeTrait<KeyOfType> */
    use \PHPStan\Type\Generic\TemplateTypeTrait;
    use UndecidedComparisonCompoundTypeTrait;
    public function __construct(\PHPStan\Type\Generic\TemplateTypeScope $scope, \PHPStan\Type\Generic\TemplateTypeStrategy $templateTypeStrategy, \PHPStan\Type\Generic\TemplateTypeVariance $templateTypeVariance, string $name, KeyOfType $bound)
    {
        parent::__construct($bound->getType());
        $this->scope = $scope;
        $this->strategy = $templateTypeStrategy;
        $this->variance = $templateTypeVariance;
        $this->name = $name;
        $this->bound = $bound;
    }
    protected function getResult() : Type
    {
        $result = $this->getBound()->getResult();
        return \PHPStan\Type\Generic\TemplateTypeFactory::create($this->getScope(), $this->getName(), $result, $this->getVariance(), $this->getStrategy());
    }
    protected function shouldGeneralizeInferredType() : bool
    {
        return \false;
    }
}
