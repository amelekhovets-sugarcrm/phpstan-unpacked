<?php

declare (strict_types=1);
namespace PHPStan\Type\Traits;

use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Type;
trait NonGeneralizableTypeTrait
{
    public function generalize(GeneralizePrecision $precision) : Type
    {
        return $this->traverse(static function (Type $type) use($precision) {
            return $type->generalize($precision);
        });
    }
}
