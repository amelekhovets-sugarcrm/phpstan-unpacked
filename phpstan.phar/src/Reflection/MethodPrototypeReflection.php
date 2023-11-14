<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PHPStan\Type\Type;
class MethodPrototypeReflection implements \PHPStan\Reflection\ClassMemberReflection
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var \PHPStan\Reflection\ClassReflection
     */
    private $declaringClass;
    /**
     * @var bool
     */
    private $isStatic;
    /**
     * @var bool
     */
    private $isPrivate;
    /**
     * @var bool
     */
    private $isPublic;
    /**
     * @var bool
     */
    private $isAbstract;
    /**
     * @var bool
     */
    private $isFinal;
    /**
     * @var ParametersAcceptor[]
     */
    private $variants;
    /**
     * @var \PHPStan\Type\Type|null
     */
    private $tentativeReturnType;
    /**
     * @param ParametersAcceptor[] $variants
     */
    public function __construct(string $name, \PHPStan\Reflection\ClassReflection $declaringClass, bool $isStatic, bool $isPrivate, bool $isPublic, bool $isAbstract, bool $isFinal, array $variants, ?Type $tentativeReturnType)
    {
        $this->name = $name;
        $this->declaringClass = $declaringClass;
        $this->isStatic = $isStatic;
        $this->isPrivate = $isPrivate;
        $this->isPublic = $isPublic;
        $this->isAbstract = $isAbstract;
        $this->isFinal = $isFinal;
        $this->variants = $variants;
        $this->tentativeReturnType = $tentativeReturnType;
    }
    public function getName() : string
    {
        return $this->name;
    }
    public function getDeclaringClass() : \PHPStan\Reflection\ClassReflection
    {
        return $this->declaringClass;
    }
    public function isStatic() : bool
    {
        return $this->isStatic;
    }
    public function isPrivate() : bool
    {
        return $this->isPrivate;
    }
    public function isPublic() : bool
    {
        return $this->isPublic;
    }
    public function isAbstract() : bool
    {
        return $this->isAbstract;
    }
    public function isFinal() : bool
    {
        return $this->isFinal;
    }
    public function getDocComment() : ?string
    {
        return null;
    }
    /**
     * @return ParametersAcceptor[]
     */
    public function getVariants() : array
    {
        return $this->variants;
    }
    public function getTentativeReturnType() : ?Type
    {
        return $this->tentativeReturnType;
    }
}
