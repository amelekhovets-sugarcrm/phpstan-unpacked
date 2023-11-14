<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeAbstract;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\Type;
/** @api */
class ClassPropertyNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var int
     */
    private $flags;
    /**
     * @var \PhpParser\Node\Identifier|\PhpParser\Node\Name|\PhpParser\Node\ComplexType|null
     */
    private $type;
    /**
     * @var \PhpParser\Node\Expr|null
     */
    private $default;
    /**
     * @var string|null
     */
    private $phpDoc;
    /**
     * @var \PHPStan\Type\Type|null
     */
    private $phpDocType;
    /**
     * @var bool
     */
    private $isPromoted;
    /**
     * @var bool
     */
    private $isPromotedFromTrait;
    /**
     * @var bool
     */
    private $isReadonlyByPhpDoc;
    /**
     * @var bool
     */
    private $isDeclaredInTrait;
    /**
     * @var bool
     */
    private $isReadonlyClass;
    /**
     * @var bool
     */
    private $isAllowedPrivateMutation;
    /**
     * @var \PHPStan\Reflection\ClassReflection
     */
    private $classReflection;
    /**
     * @param \PhpParser\Node\Identifier|\PhpParser\Node\Name|\PhpParser\Node\ComplexType|null $type
     */
    public function __construct(string $name, int $flags, $type, ?Expr $default, ?string $phpDoc, ?Type $phpDocType, bool $isPromoted, bool $isPromotedFromTrait, Node $originalNode, bool $isReadonlyByPhpDoc, bool $isDeclaredInTrait, bool $isReadonlyClass, bool $isAllowedPrivateMutation, ClassReflection $classReflection)
    {
        $this->name = $name;
        $this->flags = $flags;
        $this->type = $type;
        $this->default = $default;
        $this->phpDoc = $phpDoc;
        $this->phpDocType = $phpDocType;
        $this->isPromoted = $isPromoted;
        $this->isPromotedFromTrait = $isPromotedFromTrait;
        $this->isReadonlyByPhpDoc = $isReadonlyByPhpDoc;
        $this->isDeclaredInTrait = $isDeclaredInTrait;
        $this->isReadonlyClass = $isReadonlyClass;
        $this->isAllowedPrivateMutation = $isAllowedPrivateMutation;
        $this->classReflection = $classReflection;
        parent::__construct($originalNode->getAttributes());
    }
    public function getName() : string
    {
        return $this->name;
    }
    public function getFlags() : int
    {
        return $this->flags;
    }
    public function getDefault() : ?Expr
    {
        return $this->default;
    }
    public function isPromoted() : bool
    {
        return $this->isPromoted;
    }
    public function isPromotedFromTrait() : bool
    {
        return $this->isPromotedFromTrait;
    }
    public function getPhpDoc() : ?string
    {
        return $this->phpDoc;
    }
    public function getPhpDocType() : ?Type
    {
        return $this->phpDocType;
    }
    public function isPublic() : bool
    {
        return ($this->flags & Class_::MODIFIER_PUBLIC) !== 0 || ($this->flags & Class_::VISIBILITY_MODIFIER_MASK) === 0;
    }
    public function isProtected() : bool
    {
        return (bool) ($this->flags & Class_::MODIFIER_PROTECTED);
    }
    public function isPrivate() : bool
    {
        return (bool) ($this->flags & Class_::MODIFIER_PRIVATE);
    }
    public function isStatic() : bool
    {
        return (bool) ($this->flags & Class_::MODIFIER_STATIC);
    }
    public function isReadOnly() : bool
    {
        return (bool) ($this->flags & Class_::MODIFIER_READONLY) || $this->isReadonlyClass;
    }
    public function isReadOnlyByPhpDoc() : bool
    {
        return $this->isReadonlyByPhpDoc;
    }
    public function isDeclaredInTrait() : bool
    {
        return $this->isDeclaredInTrait;
    }
    public function isAllowedPrivateMutation() : bool
    {
        return $this->isAllowedPrivateMutation;
    }
    /**
     * @return Identifier|Name|Node\ComplexType|null
     */
    public function getNativeType()
    {
        return $this->type;
    }
    public function getClassReflection() : ClassReflection
    {
        return $this->classReflection;
    }
    public function getType() : string
    {
        return 'PHPStan_Node_ClassPropertyNode';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames() : array
    {
        return [];
    }
}
