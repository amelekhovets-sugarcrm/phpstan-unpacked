<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Native;

use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\MethodPrototypeReflection;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\Reflection\Php\BuiltinMethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
use ReflectionException;
use function strtolower;
class NativeMethodReflection implements ExtendedMethodReflection
{
    /**
     * @var \PHPStan\Reflection\ReflectionProvider
     */
    private $reflectionProvider;
    /**
     * @var \PHPStan\Reflection\ClassReflection
     */
    private $declaringClass;
    /**
     * @var \PHPStan\Reflection\Php\BuiltinMethodReflection
     */
    private $reflection;
    /**
     * @var ParametersAcceptorWithPhpDocs[]
     */
    private $variants;
    /**
     * @var \PHPStan\TrinaryLogic
     */
    private $hasSideEffects;
    /**
     * @var \PHPStan\Type\Type|null
     */
    private $throwType;
    /**
     * @var \PHPStan\Reflection\Assertions
     */
    private $assertions;
    /**
     * @var \PHPStan\Type\Type|null
     */
    private $selfOutType;
    /**
     * @var string|null
     */
    private $phpDocComment;
    /**
     * @param ParametersAcceptorWithPhpDocs[] $variants
     */
    public function __construct(ReflectionProvider $reflectionProvider, ClassReflection $declaringClass, BuiltinMethodReflection $reflection, array $variants, TrinaryLogic $hasSideEffects, ?Type $throwType, Assertions $assertions, ?Type $selfOutType, ?string $phpDocComment)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->declaringClass = $declaringClass;
        $this->reflection = $reflection;
        $this->variants = $variants;
        $this->hasSideEffects = $hasSideEffects;
        $this->throwType = $throwType;
        $this->assertions = $assertions;
        $this->selfOutType = $selfOutType;
        $this->phpDocComment = $phpDocComment;
    }
    public function getDeclaringClass() : ClassReflection
    {
        return $this->declaringClass;
    }
    public function isStatic() : bool
    {
        return $this->reflection->isStatic();
    }
    public function isPrivate() : bool
    {
        return $this->reflection->isPrivate();
    }
    public function isPublic() : bool
    {
        return $this->reflection->isPublic();
    }
    public function isAbstract() : bool
    {
        return $this->reflection->isAbstract();
    }
    public function getPrototype() : ClassMemberReflection
    {
        try {
            $prototypeMethod = $this->reflection->getPrototype();
            $prototypeDeclaringClass = $this->declaringClass->getAncestorWithClassName($prototypeMethod->getDeclaringClass()->getName());
            if ($prototypeDeclaringClass === null) {
                $prototypeDeclaringClass = $this->reflectionProvider->getClass($prototypeMethod->getDeclaringClass()->getName());
            }
            $tentativeReturnType = null;
            if ($prototypeMethod->getTentativeReturnType() !== null) {
                $tentativeReturnType = TypehintHelper::decideTypeFromReflection($prototypeMethod->getTentativeReturnType());
            }
            return new MethodPrototypeReflection($prototypeMethod->getName(), $prototypeDeclaringClass, $prototypeMethod->isStatic(), $prototypeMethod->isPrivate(), $prototypeMethod->isPublic(), $prototypeMethod->isAbstract(), $prototypeMethod->isFinal(), $prototypeDeclaringClass->getNativeMethod($prototypeMethod->getName())->getVariants(), $tentativeReturnType);
        } catch (ReflectionException $exception) {
            return $this;
        }
    }
    public function getName() : string
    {
        return $this->reflection->getName();
    }
    public function getVariants() : array
    {
        return $this->variants;
    }
    public function getDeprecatedDescription() : ?string
    {
        return null;
    }
    public function isDeprecated() : TrinaryLogic
    {
        return $this->reflection->isDeprecated();
    }
    public function isInternal() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isFinal() : TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->reflection->isFinal());
    }
    public function getThrowType() : ?Type
    {
        return $this->throwType;
    }
    public function hasSideEffects() : TrinaryLogic
    {
        $name = strtolower($this->getName());
        $isVoid = $this->isVoid();
        if ($name !== '__construct' && $isVoid) {
            return TrinaryLogic::createYes();
        }
        return $this->hasSideEffects;
    }
    private function isVoid() : bool
    {
        foreach ($this->variants as $variant) {
            if (!$variant->getReturnType()->isVoid()->yes()) {
                return \false;
            }
        }
        return \true;
    }
    public function getDocComment() : ?string
    {
        return $this->phpDocComment;
    }
    public function getAsserts() : Assertions
    {
        return $this->assertions;
    }
    public function getSelfOutType() : ?Type
    {
        return $this->selfOutType;
    }
    public function returnsByReference() : TrinaryLogic
    {
        return $this->reflection->returnsByReference();
    }
}
