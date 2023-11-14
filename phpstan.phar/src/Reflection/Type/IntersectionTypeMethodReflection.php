<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionVariantWithPhpDocs;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_map;
use function count;
use function implode;
class IntersectionTypeMethodReflection implements ExtendedMethodReflection
{
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var ExtendedMethodReflection[]
     */
    private $methods;
    /**
     * @param ExtendedMethodReflection[] $methods
     */
    public function __construct(string $methodName, array $methods)
    {
        $this->methodName = $methodName;
        $this->methods = $methods;
    }
    public function getDeclaringClass() : ClassReflection
    {
        return $this->methods[0]->getDeclaringClass();
    }
    public function isStatic() : bool
    {
        foreach ($this->methods as $method) {
            if ($method->isStatic()) {
                return \true;
            }
        }
        return \false;
    }
    public function isPrivate() : bool
    {
        foreach ($this->methods as $method) {
            if (!$method->isPrivate()) {
                return \false;
            }
        }
        return \true;
    }
    public function isPublic() : bool
    {
        foreach ($this->methods as $method) {
            if ($method->isPublic()) {
                return \true;
            }
        }
        return \false;
    }
    public function getName() : string
    {
        return $this->methodName;
    }
    public function getPrototype() : ClassMemberReflection
    {
        return $this;
    }
    public function getVariants() : array
    {
        $returnType = TypeCombinator::intersect(...array_map(static function (MethodReflection $method) : Type {
            return TypeCombinator::intersect(...array_map(static function (ParametersAcceptor $acceptor) : Type {
                return $acceptor->getReturnType();
            }, $method->getVariants()));
        }, $this->methods));
        $phpDocReturnType = TypeCombinator::intersect(...array_map(static function (MethodReflection $method) : Type {
            return TypeCombinator::intersect(...array_map(static function (ParametersAcceptor $acceptor) : Type {
                return $acceptor->getPhpDocReturnType();
            }, $method->getVariants()));
        }, $this->methods));
        $nativeReturnType = TypeCombinator::intersect(...array_map(static function (MethodReflection $method) : Type {
            return TypeCombinator::intersect(...array_map(static function (ParametersAcceptor $acceptor) : Type {
                return $acceptor->getNativeReturnType();
            }, $method->getVariants()));
        }, $this->methods));
        return array_map(static function (ParametersAcceptorWithPhpDocs $acceptor) use($returnType, $phpDocReturnType, $nativeReturnType) : ParametersAcceptorWithPhpDocs {
            return new FunctionVariantWithPhpDocs($acceptor->getTemplateTypeMap(), $acceptor->getResolvedTemplateTypeMap(), $acceptor->getParameters(), $acceptor->isVariadic(), $returnType, $phpDocReturnType, $nativeReturnType, $acceptor->getCallSiteVarianceMap());
        }, $this->methods[0]->getVariants());
    }
    public function isDeprecated() : TrinaryLogic
    {
        return TrinaryLogic::lazyMaxMin($this->methods, static function (MethodReflection $method) : TrinaryLogic {
            return $method->isDeprecated();
        });
    }
    public function getDeprecatedDescription() : ?string
    {
        $descriptions = [];
        foreach ($this->methods as $method) {
            if (!$method->isDeprecated()->yes()) {
                continue;
            }
            $description = $method->getDeprecatedDescription();
            if ($description === null) {
                continue;
            }
            $descriptions[] = $description;
        }
        if (count($descriptions) === 0) {
            return null;
        }
        return implode(' ', $descriptions);
    }
    public function isFinal() : TrinaryLogic
    {
        return TrinaryLogic::lazyMaxMin($this->methods, static function (MethodReflection $method) : TrinaryLogic {
            return $method->isFinal();
        });
    }
    public function isInternal() : TrinaryLogic
    {
        return TrinaryLogic::lazyMaxMin($this->methods, static function (MethodReflection $method) : TrinaryLogic {
            return $method->isInternal();
        });
    }
    public function getThrowType() : ?Type
    {
        $types = [];
        foreach ($this->methods as $method) {
            $type = $method->getThrowType();
            if ($type === null) {
                continue;
            }
            $types[] = $type;
        }
        if (count($types) === 0) {
            return null;
        }
        return TypeCombinator::intersect(...$types);
    }
    public function hasSideEffects() : TrinaryLogic
    {
        return TrinaryLogic::lazyMaxMin($this->methods, static function (MethodReflection $method) : TrinaryLogic {
            return $method->hasSideEffects();
        });
    }
    public function getDocComment() : ?string
    {
        return null;
    }
    public function getAsserts() : Assertions
    {
        $assertions = Assertions::createEmpty();
        foreach ($this->methods as $method) {
            $assertions = $assertions->intersectWith($method->getAsserts());
        }
        return $assertions;
    }
    public function getSelfOutType() : ?Type
    {
        return null;
    }
    public function returnsByReference() : TrinaryLogic
    {
        return TrinaryLogic::lazyMaxMin($this->methods, static function (ExtendedMethodReflection $method) : TrinaryLogic {
            return $method->returnsByReference();
        });
    }
}
