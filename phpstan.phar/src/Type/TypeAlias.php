<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
class TypeAlias
{
    /**
     * @var \PHPStan\PhpDocParser\Ast\Type\TypeNode
     */
    private $typeNode;
    /**
     * @var \PHPStan\Analyser\NameScope
     */
    private $nameScope;
    /**
     * @var \PHPStan\Type\Type|null
     */
    private $resolvedType;
    public function __construct(TypeNode $typeNode, NameScope $nameScope)
    {
        $this->typeNode = $typeNode;
        $this->nameScope = $nameScope;
    }
    public static function invalid() : self
    {
        $self = new self(new IdentifierTypeNode('*ERROR*'), new NameScope(null, []));
        $self->resolvedType = new \PHPStan\Type\CircularTypeAliasErrorType();
        return $self;
    }
    public function resolve(TypeNodeResolver $typeNodeResolver) : \PHPStan\Type\Type
    {
        if ($this->resolvedType === null) {
            $this->resolvedType = $typeNodeResolver->resolve($this->typeNode, $this->nameScope);
        }
        return $this->resolvedType;
    }
}
