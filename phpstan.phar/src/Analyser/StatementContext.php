<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

class StatementContext
{
    /**
     * @var bool
     */
    private $isTopLevel;
    private function __construct(bool $isTopLevel)
    {
        $this->isTopLevel = $isTopLevel;
    }
    public static function createTopLevel() : self
    {
        return new self(\true);
    }
    public static function createDeep() : self
    {
        return new self(\false);
    }
    public function isTopLevel() : bool
    {
        return $this->isTopLevel;
    }
    public function enterDeep() : self
    {
        if ($this->isTopLevel) {
            return self::createDeep();
        }
        return $this;
    }
}
