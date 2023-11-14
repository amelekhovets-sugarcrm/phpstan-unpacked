<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Native;

use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
class NativeFunctionReflection implements FunctionReflection
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var ParametersAcceptorWithPhpDocs[]
     */
    private $variants;
    /**
     * @var \PHPStan\Type\Type|null
     */
    private $throwType;
    /**
     * @var \PHPStan\TrinaryLogic
     */
    private $hasSideEffects;
    /**
     * @var bool
     */
    private $isDeprecated;
    /**
     * @var string|null
     */
    private $phpDocComment;
    /**
     * @var \PHPStan\Reflection\Assertions
     */
    private $assertions;
    /**
     * @var \PHPStan\TrinaryLogic
     */
    private $returnsByReference;
    /**
     * @param ParametersAcceptorWithPhpDocs[] $variants
     */
    public function __construct(string $name, array $variants, ?Type $throwType, TrinaryLogic $hasSideEffects, bool $isDeprecated, ?Assertions $assertions = null, ?string $phpDocComment = null, ?TrinaryLogic $returnsByReference = null)
    {
        $this->name = $name;
        $this->variants = $variants;
        $this->throwType = $throwType;
        $this->hasSideEffects = $hasSideEffects;
        $this->isDeprecated = $isDeprecated;
        $this->phpDocComment = $phpDocComment;
        $this->assertions = $assertions ?? Assertions::createEmpty();
        $this->returnsByReference = $returnsByReference ?? TrinaryLogic::createMaybe();
    }
    public function getName() : string
    {
        return $this->name;
    }
    public function getFileName() : ?string
    {
        return null;
    }
    /**
     * @return ParametersAcceptorWithPhpDocs[]
     */
    public function getVariants() : array
    {
        return $this->variants;
    }
    public function getThrowType() : ?Type
    {
        return $this->throwType;
    }
    public function getDeprecatedDescription() : ?string
    {
        return null;
    }
    public function isDeprecated() : TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->isDeprecated);
    }
    public function isInternal() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isFinal() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function hasSideEffects() : TrinaryLogic
    {
        if ($this->isVoid()) {
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
    public function isBuiltin() : bool
    {
        return \true;
    }
    public function getAsserts() : Assertions
    {
        return $this->assertions;
    }
    public function getDocComment() : ?string
    {
        return $this->phpDocComment;
    }
    public function returnsByReference() : TrinaryLogic
    {
        return $this->returnsByReference;
    }
}
