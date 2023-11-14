<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\TrinaryLogic;
/** @api */
interface CompoundType extends \PHPStan\Type\Type
{
    public function isSubTypeOf(\PHPStan\Type\Type $otherType) : TrinaryLogic;
    public function isAcceptedBy(\PHPStan\Type\Type $acceptingType, bool $strictTypes) : TrinaryLogic;
    public function isAcceptedWithReasonBy(\PHPStan\Type\Type $acceptingType, bool $strictTypes) : \PHPStan\Type\AcceptsResult;
    public function isGreaterThan(\PHPStan\Type\Type $otherType) : TrinaryLogic;
    public function isGreaterThanOrEqual(\PHPStan\Type\Type $otherType) : TrinaryLogic;
}
