<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\TrinaryLogic;
/** @api */
class NonAcceptingNeverType extends \PHPStan\Type\NeverType
{
    /** @api */
    public function __construct()
    {
        parent::__construct(\true);
    }
    public function isSuperTypeOf(\PHPStan\Type\Type $type) : TrinaryLogic
    {
        if ($type instanceof self) {
            return TrinaryLogic::createYes();
        }
        if ($type instanceof parent) {
            return TrinaryLogic::createMaybe();
        }
        return TrinaryLogic::createNo();
    }
    public function acceptsWithReason(\PHPStan\Type\Type $type, bool $strictTypes) : \PHPStan\Type\AcceptsResult
    {
        if ($type instanceof \PHPStan\Type\NeverType) {
            return \PHPStan\Type\AcceptsResult::createYes();
        }
        return \PHPStan\Type\AcceptsResult::createNo();
    }
    public function describe(\PHPStan\Type\VerbosityLevel $level) : string
    {
        return 'never';
    }
}
