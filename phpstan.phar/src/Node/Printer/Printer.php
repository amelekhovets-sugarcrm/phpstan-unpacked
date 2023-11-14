<?php

declare (strict_types=1);
namespace PHPStan\Node\Printer;

use PhpParser\PrettyPrinter\Standard;
use PHPStan\Node\Expr\AlwaysRememberedExpr;
use PHPStan\Node\Expr\GetIterableKeyTypeExpr;
use PHPStan\Node\Expr\GetIterableValueTypeExpr;
use PHPStan\Node\Expr\GetOffsetValueTypeExpr;
use PHPStan\Node\Expr\OriginalPropertyTypeExpr;
use PHPStan\Node\Expr\PropertyInitializationExpr;
use PHPStan\Node\Expr\SetOffsetValueTypeExpr;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
class Printer extends Standard
{
    public function __construct()
    {
        parent::__construct(['shortArraySyntax' => \true]);
    }
    protected function pPHPStan_Node_TypeExpr(TypeExpr $expr) : string
    {
        return sprintf('__phpstanType(%s)', $expr->getExprType()->describe(VerbosityLevel::precise()));
    }
    protected function pPHPStan_Node_GetOffsetValueTypeExpr(GetOffsetValueTypeExpr $expr) : string
    {
        return sprintf('__phpstanGetOffsetValueType(%s, %s)', $this->p($expr->getVar()), $this->p($expr->getDim()));
    }
    protected function pPHPStan_Node_GetIterableValueTypeExpr(GetIterableValueTypeExpr $expr) : string
    {
        return sprintf('__phpstanGetIterableValueType(%s)', $this->p($expr->getExpr()));
    }
    protected function pPHPStan_Node_GetIterableKeyTypeExpr(GetIterableKeyTypeExpr $expr) : string
    {
        return sprintf('__phpstanGetIterableKeyType(%s)', $this->p($expr->getExpr()));
    }
    protected function pPHPStan_Node_OriginalPropertyTypeExpr(OriginalPropertyTypeExpr $expr) : string
    {
        return sprintf('__phpstanOriginalPropertyType(%s)', $this->p($expr->getPropertyFetch()));
    }
    protected function pPHPStan_Node_SetOffsetValueTypeExpr(SetOffsetValueTypeExpr $expr) : string
    {
        return sprintf('__phpstanSetOffsetValueType(%s, %s, %s)', $this->p($expr->getVar()), $expr->getDim() !== null ? $this->p($expr->getDim()) : 'null', $this->p($expr->getValue()));
    }
    protected function pPHPStan_Node_AlwaysRememberedExpr(AlwaysRememberedExpr $expr) : string
    {
        return sprintf('__phpstanRembered(%s)', $this->p($expr->getExpr()));
    }
    protected function pPHPStan_Node_PropertyInitializationExpr(PropertyInitializationExpr $expr) : string
    {
        return sprintf('__phpstanPropertyInitialization(%s)', $expr->getPropertyName());
    }
}