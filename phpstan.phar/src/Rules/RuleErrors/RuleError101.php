<?php

declare (strict_types=1);
namespace PHPStan\Rules\RuleErrors;

use PHPStan\Rules\FileRuleError;
use PHPStan\Rules\MetadataRuleError;
use PHPStan\Rules\NonIgnorableRuleError;
use PHPStan\Rules\RuleError;
/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
class RuleError101 implements RuleError, FileRuleError, MetadataRuleError, NonIgnorableRuleError
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
    /** @var mixed[] */
    public $metadata;
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
    /**
     * @return mixed[]
     */
    public function getMetadata() : array
    {
        return $this->metadata;
    }
}
