<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\TrinaryLogic;
use stdClass;
class ObjectShapePropertyReflection implements PropertyReflection
{
    /**
     * @var \PHPStan\Type\Type
     */
    private $type;
    public function __construct(\PHPStan\Type\Type $type)
    {
        $this->type = $type;
    }
    public function getDeclaringClass() : ClassReflection
    {
        $reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
        return $reflectionProvider->getClass(stdClass::class);
    }
    public function isStatic() : bool
    {
        return \false;
    }
    public function isPrivate() : bool
    {
        return \false;
    }
    public function isPublic() : bool
    {
        return \true;
    }
    public function getDocComment() : ?string
    {
        return null;
    }
    public function getReadableType() : \PHPStan\Type\Type
    {
        return $this->type;
    }
    public function getWritableType() : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\NeverType();
    }
    public function canChangeTypeAfterAssignment() : bool
    {
        return \false;
    }
    public function isReadable() : bool
    {
        return \true;
    }
    public function isWritable() : bool
    {
        return \false;
    }
    public function isDeprecated() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function getDeprecatedDescription() : ?string
    {
        return null;
    }
    public function isInternal() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
}
