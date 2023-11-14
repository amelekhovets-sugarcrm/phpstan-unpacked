<?php

declare (strict_types=1);
namespace PHPStan\Rules\RuleErrors;

use PHPStan\Rules\FileRuleError;
use PHPStan\Rules\RuleError;
/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
class RuleError5 implements RuleError, FileRuleError
{
    /**
     * @var string
     */
    public $message;
    /**
     * @var string
     */
    public $file;
    /**
     * @var string
     */
    public $fileDescription;
    public function getMessage() : string
    {
        return $this->message;
    }
    public function getFile() : string
    {
        return $this->file;
    }
    public function getFileDescription() : string
    {
        return $this->fileDescription;
    }
}
