<?php

declare (strict_types=1);
namespace PHPStan\Type\Generic;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\SubtractableType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
/**
 * @template TBound of Type
 */
trait TemplateTypeTrait
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var \PHPStan\Type\Generic\TemplateTypeScope
     */
    private $scope;
    /**
     * @var \PHPStan\Type\Generic\TemplateTypeStrategy
     */
    private $strategy;
    /**
     * @var \PHPStan\Type\Generic\TemplateTypeVariance
     */
    private $variance;
    /** @var TBound */
    private $bound;
    public function getName() : string
    {
        return $this->name;
    }
    public function getScope() : \PHPStan\Type\Generic\TemplateTypeScope
    {
        return $this->scope;
    }
    /** @return TBound */
    public function getBound() : Type
    {
        return $this->bound;
    }
    public function describe(VerbosityLevel $level) : string
    {
        $basicDescription = function () use($level) : string {
            // @phpstan-ignore-next-line
            if ($this->bound instanceof MixedType && $this->bound->getSubtractedType() === null && !$this->bound instanceof \PHPStan\Type\Generic\TemplateMixedType) {
                $boundDescription = '';
            } else {
                // @phpstan-ignore-line
                $boundDescription = sprintf(' of %s', $this->bound->describe($level));
            }
            return sprintf('%s%s', $this->name, $boundDescription);
        };
        return $level->handle($basicDescription, $basicDescription, function () use($basicDescription) : string {
            return sprintf('%s (%s, %s)', $basicDescription(), $this->scope->describe(), $this->isArgument() ? 'argument' : 'parameter');
        });
    }
    public function isArgument() : bool
    {
        return $this->strategy->isArgument();
    }
    public function toArgument() : \PHPStan\Type\Generic\TemplateType
    {
        return new self($this->scope, new \PHPStan\Type\Generic\TemplateTypeArgumentStrategy(), $this->variance, $this->name, \PHPStan\Type\Generic\TemplateTypeHelper::toArgument($this->getBound()));
    }
    public function isValidVariance(Type $a, Type $b) : TrinaryLogic
    {
        return $this->isValidVarianceWithReason($a, $b)->result;
    }
    public function isValidVarianceWithReason(Type $a, Type $b) : AcceptsResult
    {
        return $this->variance->isValidVarianceWithReason($this, $a, $b);
    }
    public function subtract(Type $typeToRemove) : Type
    {
        $removedBound = TypeCombinator::remove($this->getBound(), $typeToRemove);
        return \PHPStan\Type\Generic\TemplateTypeFactory::create($this->getScope(), $this->getName(), $removedBound, $this->getVariance(), $this->getStrategy());
    }
    public function getTypeWithoutSubtractedType() : Type
    {
        $bound = $this->getBound();
        if (!$bound instanceof SubtractableType) {
            // @phpstan-ignore-line
            return $this;
        }
        return \PHPStan\Type\Generic\TemplateTypeFactory::create($this->getScope(), $this->getName(), $bound->getTypeWithoutSubtractedType(), $this->getVariance(), $this->getStrategy());
    }
    public function changeSubtractedType(?Type $subtractedType) : Type
    {
        $bound = $this->getBound();
        if (!$bound instanceof SubtractableType) {
            // @phpstan-ignore-line
            return $this;
        }
        return \PHPStan\Type\Generic\TemplateTypeFactory::create($this->getScope(), $this->getName(), $bound->changeSubtractedType($subtractedType), $this->getVariance(), $this->getStrategy());
    }
    public function getSubtractedType() : ?Type
    {
        $bound = $this->getBound();
        if (!$bound instanceof SubtractableType) {
            // @phpstan-ignore-line
            return null;
        }
        return $bound->getSubtractedType();
    }
    public function equals(Type $type) : bool
    {
        return $type instanceof self && $type->scope->equals($this->scope) && $type->name === $this->name && $this->bound->equals($type->bound);
    }
    public function isAcceptedBy(Type $acceptingType, bool $strictTypes) : TrinaryLogic
    {
        return $this->isAcceptedWithReasonBy($acceptingType, $strictTypes)->result;
    }
    public function isAcceptedWithReasonBy(Type $acceptingType, bool $strictTypes) : AcceptsResult
    {
        /** @var TBound $bound */
        $bound = $this->getBound();
        if (!$acceptingType instanceof $bound && !$this instanceof $acceptingType && !$acceptingType instanceof \PHPStan\Type\Generic\TemplateType && ($acceptingType instanceof UnionType || $acceptingType instanceof IntersectionType)) {
            return $acceptingType->acceptsWithReason($this, $strictTypes);
        }
        if (!$acceptingType instanceof \PHPStan\Type\Generic\TemplateType) {
            return $acceptingType->acceptsWithReason($this->getBound(), $strictTypes);
        }
        if ($this->getScope()->equals($acceptingType->getScope()) && $this->getName() === $acceptingType->getName()) {
            return $acceptingType->getBound()->acceptsWithReason($this->getBound(), $strictTypes);
        }
        return $acceptingType->getBound()->acceptsWithReason($this->getBound(), $strictTypes)->and(new AcceptsResult(TrinaryLogic::createMaybe(), []));
    }
    public function accepts(Type $type, bool $strictTypes) : TrinaryLogic
    {
        return $this->acceptsWithReason($type, $strictTypes)->result;
    }
    public function acceptsWithReason(Type $type, bool $strictTypes) : AcceptsResult
    {
        return $this->strategy->accepts($this, $type, $strictTypes);
    }
    public function isSuperTypeOf(Type $type) : TrinaryLogic
    {
        if ($type instanceof \PHPStan\Type\Generic\TemplateType || $type instanceof IntersectionType) {
            return $type->isSubTypeOf($this);
        }
        if ($type instanceof NeverType) {
            return TrinaryLogic::createYes();
        }
        return $this->getBound()->isSuperTypeOf($type)->and(TrinaryLogic::createMaybe());
    }
    public function isSubTypeOf(Type $type) : TrinaryLogic
    {
        /** @var TBound $bound */
        $bound = $this->getBound();
        if (!$type instanceof $bound && !$this instanceof $type && !$type instanceof \PHPStan\Type\Generic\TemplateType && ($type instanceof UnionType || $type instanceof IntersectionType)) {
            return $type->isSuperTypeOf($this);
        }
        if (!$type instanceof \PHPStan\Type\Generic\TemplateType) {
            return $type->isSuperTypeOf($this->getBound());
        }
        if ($this->getScope()->equals($type->getScope()) && $this->getName() === $type->getName()) {
            return $type->getBound()->isSuperTypeOf($this->getBound());
        }
        return $type->getBound()->isSuperTypeOf($this->getBound())->and(TrinaryLogic::createMaybe());
    }
    public function toArrayKey() : Type
    {
        return $this;
    }
    public function inferTemplateTypes(Type $receivedType) : \PHPStan\Type\Generic\TemplateTypeMap
    {
        if ($receivedType instanceof \PHPStan\Type\Generic\TemplateType && $this->getBound()->isSuperTypeOf($receivedType->getBound())->yes()) {
            return new \PHPStan\Type\Generic\TemplateTypeMap([$this->name => $receivedType]);
        }
        $map = $this->getBound()->inferTemplateTypes($receivedType);
        $resolvedBound = TypeUtils::resolveLateResolvableTypes(\PHPStan\Type\Generic\TemplateTypeHelper::resolveTemplateTypes($this->getBound(), $map, \PHPStan\Type\Generic\TemplateTypeVarianceMap::createEmpty(), \PHPStan\Type\Generic\TemplateTypeVariance::createStatic()));
        if ($resolvedBound->isSuperTypeOf($receivedType)->yes()) {
            if ($this->shouldGeneralizeInferredType()) {
                $generalizedType = $receivedType->generalize(GeneralizePrecision::templateArgument());
                if ($resolvedBound->isSuperTypeOf($generalizedType)->yes()) {
                    $receivedType = $generalizedType;
                }
            }
            return (new \PHPStan\Type\Generic\TemplateTypeMap([$this->name => $receivedType]))->union($map);
        }
        return $map;
    }
    public function getReferencedTemplateTypes(\PHPStan\Type\Generic\TemplateTypeVariance $positionVariance) : array
    {
        return [new \PHPStan\Type\Generic\TemplateTypeReference($this, $positionVariance)];
    }
    public function getVariance() : \PHPStan\Type\Generic\TemplateTypeVariance
    {
        return $this->variance;
    }
    public function getStrategy() : \PHPStan\Type\Generic\TemplateTypeStrategy
    {
        return $this->strategy;
    }
    protected function shouldGeneralizeInferredType() : bool
    {
        return \true;
    }
    public function traverse(callable $cb) : Type
    {
        $bound = $cb($this->getBound());
        if ($this->getBound() === $bound) {
            return $this;
        }
        return \PHPStan\Type\Generic\TemplateTypeFactory::create($this->getScope(), $this->getName(), $bound, $this->getVariance(), $this->getStrategy());
    }
    public function traverseSimultaneously(Type $right, callable $cb) : Type
    {
        if (!$right instanceof \PHPStan\Type\Generic\TemplateType) {
            return $this;
        }
        $bound = $cb($this->getBound(), $right->getBound());
        if ($this->getBound() === $bound) {
            return $this;
        }
        return \PHPStan\Type\Generic\TemplateTypeFactory::create($this->getScope(), $this->getName(), $bound, $this->getVariance(), $this->getStrategy());
    }
    public function tryRemove(Type $typeToRemove) : ?Type
    {
        $bound = TypeCombinator::remove($this->getBound(), $typeToRemove);
        if ($this->getBound() === $bound) {
            return null;
        }
        return \PHPStan\Type\Generic\TemplateTypeFactory::create($this->getScope(), $this->getName(), $bound, $this->getVariance(), $this->getStrategy());
    }
    public function toPhpDocNode() : TypeNode
    {
        return new IdentifierTypeNode($this->name);
    }
    /**
     * @param mixed[] $properties
     */
    public static function __set_state(array $properties) : Type
    {
        return new self($properties['scope'], $properties['strategy'], $properties['variance'], $properties['name'], $properties['bound']);
    }
}
