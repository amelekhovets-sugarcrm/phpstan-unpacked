<?php

declare (strict_types=1);
namespace PHPStan\Rules\RuleErrors;

use PHPStan\Rules\FileRuleError;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\MetadataRuleError;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\TipRuleError;
/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
class RuleError61 implements RuleError, FileRuleError, TipRuleError, IdentifierRuleError, MetadataRuleError
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
    /**
     * @var string
     */
    public $tip;
    /**
     * @var string
     */
    public $identifier;
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
    public function getTip() : string
    {
        return $this->tip;
    }
    public function getIdentifier() : string
    {
        return $this->identifier;
    }
    /**
     * @return mixed[]
     */
    public function getMetadata() : array
    {
        return $this->metadata;
    }
}