<?php

declare (strict_types=1);
namespace PHPStan\Rules\RuleErrors;

use PHPStan\Rules\RuleError;
/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
class RuleError1 implements RuleError
{
    /**
     * @var string
     */
    public $message;
    public function getMessage() : string
    {
        return $this->message;
    }
}
