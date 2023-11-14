<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PhpParser\Node\Expr;
use PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClassConstant;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use const NAN;
class ClassConstantReflection implements \PHPStan\Reflection\ConstantReflection
{
    /**
     * @var \PHPStan\Reflection\InitializerExprTypeResolver
     */
    private $initializerExprTypeResolver;
    /**
     * @var \PHPStan\Reflection\ClassReflection
     */
    private $declaringClass;
    /**
     * @var \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClassConstant
     */
    private $reflection;
    /**
     * @var \PHPStan\Type\Type|null
     */
    private $phpDocType;
    /**
     * @var string|null
     */
    private $deprecatedDescription;
    /**
     * @var bool
     */
    private $isDeprecated;
    /**
     * @var bool
     */
    private $isInternal;
    /**
     * @var \PHPStan\Type\Type|null
     */
    private $valueType;
    public function __construct(\PHPStan\Reflection\InitializerExprTypeResolver $initializerExprTypeResolver, \PHPStan\Reflection\ClassReflection $declaringClass, ReflectionClassConstant $reflection, ?Type $phpDocType, ?string $deprecatedDescription, bool $isDeprecated, bool $isInternal)
    {
        $this->initializerExprTypeResolver = $initializerExprTypeResolver;
        $this->declaringClass = $declaringClass;
        $this->reflection = $reflection;
        $this->phpDocType = $phpDocType;
        $this->deprecatedDescription = $deprecatedDescription;
        $this->isDeprecated = $isDeprecated;
        $this->isInternal = $isInternal;
    }
    public function getName() : string
    {
        return $this->reflection->getName();
    }
    public function getFileName() : ?string
    {
        return $this->declaringClass->getFileName();
    }
    /**
     * @deprecated Use getValueExpr()
     * @return mixed
     */
    public function getValue()
    {
        try {
            return $this->reflection->getValue();
        } catch (UnableToCompileNode $exception) {
            return NAN;
        }
    }
    public function getValueExpr() : Expr
    {
        return $this->reflection->getValueExpression();
    }
    public function hasPhpDocType() : bool
    {
        return $this->phpDocType !== null;
    }
    public function getValueType() : Type
    {
        if ($this->valueType === null) {
            if ($this->phpDocType === null) {
                $this->valueType = $this->initializerExprTypeResolver->getType($this->getValueExpr(), \PHPStan\Reflection\InitializerExprContext::fromClassReflection($this->declaringClass));
            } else {
                $this->valueType = $this->phpDocType;
            }
        }
        return $this->valueType;
    }
    public function getDeclaringClass() : \PHPStan\Reflection\ClassReflection
    {
        return $this->declaringClass;
    }
    public function isStatic() : bool
    {
        return \true;
    }
    public function isPrivate() : bool
    {
        return $this->reflection->isPrivate();
    }
    public function isPublic() : bool
    {
        return $this->reflection->isPublic();
    }
    public function isFinal() : bool
    {
        return $this->reflection->isFinal();
    }
    public function isDeprecated() : TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->isDeprecated);
    }
    public function getDeprecatedDescription() : ?string
    {
        if ($this->isDeprecated) {
            return $this->deprecatedDescription;
        }
        return null;
    }
    public function isInternal() : TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->isInternal);
    }
    public function getDocComment() : ?string
    {
        $docComment = $this->reflection->getDocComment();
        if ($docComment === \false) {
            return null;
        }
        return $docComment;
    }
}
