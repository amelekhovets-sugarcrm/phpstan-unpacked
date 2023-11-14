<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

interface WrapperPropertyReflection extends \PHPStan\Reflection\PropertyReflection
{
    public function getOriginalReflection() : \PHPStan\Reflection\PropertyReflection;
}
