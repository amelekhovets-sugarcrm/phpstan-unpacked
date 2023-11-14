<?php

declare (strict_types=1);
namespace PHPStan\Rules;

use PHPStan\Type\Type;
/** @api */
class FoundTypeResult
{
    /**
     * @var \PHPStan\Type\Type
     */
    private $type;
    /**
     * @var string[]
     */
    private $referencedClasses;
    /**
     * @var RuleError[]
     */
    private $unknownClassErrors;
    /**
     * @var string|null
     */
    private $tip;
    /**
     * @param string[] $referencedClasses
     * @param RuleError[] $unknownClassErrors
     */
    public function __construct(Type $type, array $referencedClasses, array $unknownClassErrors, ?string $tip)
    {
        $this->type = $type;
        $this->referencedClasses = $referencedClasses;
        $this->unknownClassErrors = $unknownClassErrors;
        $this->tip = $tip;
    }
    public function getType() : Type
    {
        return $this->type;
    }
    /**
     * @return string[]
     */
    public function getReferencedClasses() : array
    {
        return $this->referencedClasses;
    }
    /**
     * @return RuleError[]
     */
    public function getUnknownClassErrors() : array
    {
        return $this->unknownClassErrors;
    }
    public function getTip() : ?string
    {
        return $this->tip;
    }
}
