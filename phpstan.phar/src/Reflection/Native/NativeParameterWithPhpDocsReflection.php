<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Native;

use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\Type;
class NativeParameterWithPhpDocsReflection implements ParameterReflectionWithPhpDocs
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var bool
     */
    private $optional;
    /**
     * @var \PHPStan\Type\Type
     */
    private $type;
    /**
     * @var \PHPStan\Type\Type
     */
    private $phpDocType;
    /**
     * @var \PHPStan\Type\Type
     */
    private $nativeType;
    /**
     * @var \PHPStan\Reflection\PassedByReference
     */
    private $passedByReference;
    /**
     * @var bool
     */
    private $variadic;
    /**
     * @var \PHPStan\Type\Type|null
     */
    private $defaultValue;
    /**
     * @var \PHPStan\Type\Type|null
     */
    private $outType;
    public function __construct(string $name, bool $optional, Type $type, Type $phpDocType, Type $nativeType, PassedByReference $passedByReference, bool $variadic, ?Type $defaultValue, ?Type $outType)
    {
        $this->name = $name;
        $this->optional = $optional;
        $this->type = $type;
        $this->phpDocType = $phpDocType;
        $this->nativeType = $nativeType;
        $this->passedByReference = $passedByReference;
        $this->variadic = $variadic;
        $this->defaultValue = $defaultValue;
        $this->outType = $outType;
    }
    public function getName() : string
    {
        return $this->name;
    }
    public function isOptional() : bool
    {
        return $this->optional;
    }
    public function getType() : Type
    {
        return $this->type;
    }
    public function getPhpDocType() : Type
    {
        return $this->phpDocType;
    }
    public function getNativeType() : Type
    {
        return $this->nativeType;
    }
    public function passedByReference() : PassedByReference
    {
        return $this->passedByReference;
    }
    public function isVariadic() : bool
    {
        return $this->variadic;
    }
    public function getDefaultValue() : ?Type
    {
        return $this->defaultValue;
    }
    public function getOutType() : ?Type
    {
        return $this->outType;
    }
    /**
     * @param mixed[] $properties
     */
    public static function __set_state(array $properties) : self
    {
        return new self($properties['name'], $properties['optional'], $properties['type'], $properties['phpDocType'], $properties['nativeType'], $properties['passedByReference'], $properties['variadic'], $properties['defaultValue'], $properties['outType']);
    }
}
