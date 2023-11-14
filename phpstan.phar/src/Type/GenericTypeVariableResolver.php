<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Type\Generic\TemplateTypeHelper;
/** @api */
class GenericTypeVariableResolver
{
    /**
     * @deprecated Use Type::getTemplateType() instead.
     */
    public static function getType(\PHPStan\Type\TypeWithClassName $type, string $genericClassName, string $typeVariableName) : ?\PHPStan\Type\Type
    {
        $classReflection = $type->getClassReflection();
        if ($classReflection === null) {
            return null;
        }
        $ancestorClassReflection = $classReflection->getAncestorWithClassName($genericClassName);
        if ($ancestorClassReflection === null) {
            return null;
        }
        $activeTemplateTypeMap = $ancestorClassReflection->getPossiblyIncompleteActiveTemplateTypeMap();
        $type = $activeTemplateTypeMap->getType($typeVariableName);
        if ($type instanceof \PHPStan\Type\ErrorType) {
            $templateTypeMap = $ancestorClassReflection->getTemplateTypeMap();
            $templateType = $templateTypeMap->getType($typeVariableName);
            if ($templateType === null) {
                return $type;
            }
            $bound = TemplateTypeHelper::resolveToBounds($templateType);
            if ($bound instanceof \PHPStan\Type\MixedType && $bound->isExplicitMixed()) {
                return new \PHPStan\Type\MixedType(\false);
            }
            return $bound;
        }
        return $type;
    }
}
