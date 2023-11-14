<?php

declare (strict_types=1);
namespace PHPStan\Type\Generic;

use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Type;
/** @api */
interface TemplateType extends CompoundType
{
    public function getName() : string;
    public function getScope() : \PHPStan\Type\Generic\TemplateTypeScope;
    public function getBound() : Type;
    public function toArgument() : \PHPStan\Type\Generic\TemplateType;
    public function isArgument() : bool;
    public function isValidVariance(Type $a, Type $b) : TrinaryLogic;
    public function isValidVarianceWithReason(Type $a, Type $b) : AcceptsResult;
    public function getVariance() : \PHPStan\Type\Generic\TemplateTypeVariance;
    public function getStrategy() : \PHPStan\Type\Generic\TemplateTypeStrategy;
}
