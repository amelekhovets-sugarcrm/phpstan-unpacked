<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Php;

use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\Type;
class DummyParameterWithPhpDocs extends \PHPStan\Reflection\Php\DummyParameter implements ParameterReflectionWithPhpDocs
{
    /**
     * @var \PHPStan\Type\Type
     */
    private $nativeType;
    /**
     * @var \PHPStan\Type\Type
     */
    private $phpDocType;
    /**
     * @var \PHPStan\Type\Type|null
     */
    private $outType;
    public function __construct(string $name, Type $type, bool $optional, ?PassedByReference $passedByReference, bool $variadic, ?Type $defaultValue, Type $nativeType, Type $phpDocType, ?Type $outType)
    {
        $this->nativeType = $nativeType;
        $this->phpDocType = $phpDocType;
        $this->outType = $outType;
        parent::__construct($name, $type, $optional, $passedByReference, $variadic, $defaultValue);
    }
    public function getPhpDocType() : Type
    {
        return $this->phpDocType;
    }
    public function getNativeType() : Type
    {
        return $this->nativeType;
    }
    public function getOutType() : ?Type
    {
        return $this->outType;
    }
}
