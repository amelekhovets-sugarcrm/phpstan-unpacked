<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Php;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionIntersectionType;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionNamedType;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionUnionType;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
/** @api */
class PhpPropertyReflection implements PropertyReflection
{
    /**
     * @var \PHPStan\Reflection\ClassReflection
     */
    private $declaringClass;
    /**
     * @var \PHPStan\Reflection\ClassReflection|null
     */
    private $declaringTrait;
    /**
     * @var \PHPStan\BetterReflection\Reflection\Adapter\ReflectionUnionType|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionNamedType|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionIntersectionType|null
     */
    private $nativeType;
    /**
     * @var \PHPStan\Type\Type|null
     */
    private $phpDocType;
    /**
     * @var \PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty
     */
    private $reflection;
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
     * @var bool
     */
    private $isReadOnlyByPhpDoc;
    /**
     * @var bool
     */
    private $isAllowedPrivateMutation;
    /**
     * @var \PHPStan\Type\Type|null
     */
    private $finalNativeType;
    /**
     * @var \PHPStan\Type\Type|null
     */
    private $type;
    /**
     * @param \PHPStan\BetterReflection\Reflection\Adapter\ReflectionUnionType|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionNamedType|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionIntersectionType|null $nativeType
     */
    public function __construct(ClassReflection $declaringClass, ?ClassReflection $declaringTrait, $nativeType, ?Type $phpDocType, ReflectionProperty $reflection, ?string $deprecatedDescription, bool $isDeprecated, bool $isInternal, bool $isReadOnlyByPhpDoc, bool $isAllowedPrivateMutation)
    {
        $this->declaringClass = $declaringClass;
        $this->declaringTrait = $declaringTrait;
        $this->nativeType = $nativeType;
        $this->phpDocType = $phpDocType;
        $this->reflection = $reflection;
        $this->deprecatedDescription = $deprecatedDescription;
        $this->isDeprecated = $isDeprecated;
        $this->isInternal = $isInternal;
        $this->isReadOnlyByPhpDoc = $isReadOnlyByPhpDoc;
        $this->isAllowedPrivateMutation = $isAllowedPrivateMutation;
    }
    public function getDeclaringClass() : ClassReflection
    {
        return $this->declaringClass;
    }
    public function getDeclaringTrait() : ?ClassReflection
    {
        return $this->declaringTrait;
    }
    public function getDocComment() : ?string
    {
        $docComment = $this->reflection->getDocComment();
        if ($docComment === \false) {
            return null;
        }
        return $docComment;
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
    public function isReadOnly() : bool
    {
        return $this->reflection->isReadOnly();
    }
    public function isReadOnlyByPhpDoc() : bool
    {
        return $this->isReadOnlyByPhpDoc;
    }
    public function getReadableType() : Type
    {
        if ($this->type === null) {
            $this->type = TypehintHelper::decideTypeFromReflection($this->nativeType, $this->phpDocType, $this->declaringClass);
        }
        return $this->type;
    }
    public function getWritableType() : Type
    {
        return $this->getReadableType();
    }
    public function canChangeTypeAfterAssignment() : bool
    {
        return \true;
    }
    public function isPromoted() : bool
    {
        return $this->reflection->isPromoted();
    }
    public function hasPhpDocType() : bool
    {
        return $this->phpDocType !== null;
    }
    public function getPhpDocType() : Type
    {
        if ($this->phpDocType !== null) {
            return $this->phpDocType;
        }
        return new MixedType();
    }
    public function hasNativeType() : bool
    {
        return $this->nativeType !== null;
    }
    public function getNativeType() : Type
    {
        if ($this->finalNativeType === null) {
            $this->finalNativeType = TypehintHelper::decideTypeFromReflection($this->nativeType, null, $this->declaringClass);
        }
        return $this->finalNativeType;
    }
    public function isReadable() : bool
    {
        return \true;
    }
    public function isWritable() : bool
    {
        return \true;
    }
    public function getDeprecatedDescription() : ?string
    {
        if ($this->isDeprecated) {
            return $this->deprecatedDescription;
        }
        return null;
    }
    public function isDeprecated() : TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->isDeprecated);
    }
    public function isInternal() : TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->isInternal);
    }
    public function isAllowedPrivateMutation() : bool
    {
        return $this->isAllowedPrivateMutation;
    }
    public function getNativeReflection() : ReflectionProperty
    {
        return $this->reflection;
    }
}
