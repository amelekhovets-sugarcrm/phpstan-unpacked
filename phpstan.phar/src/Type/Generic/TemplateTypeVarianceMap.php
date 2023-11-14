<?php

declare (strict_types=1);
namespace PHPStan\Type\Generic;

use function array_key_exists;
/** @api */
class TemplateTypeVarianceMap
{
    /**
     * @var array<string, TemplateTypeVariance>
     */
    private $variances;
    /**
     * @var \PHPStan\Type\Generic\TemplateTypeVarianceMap|null
     */
    private static $empty;
    /**
     * @api
     * @param array<string, TemplateTypeVariance> $variances
     */
    public function __construct(array $variances)
    {
        $this->variances = $variances;
    }
    public static function createEmpty() : self
    {
        $empty = self::$empty;
        if ($empty !== null) {
            return $empty;
        }
        $empty = new self([]);
        self::$empty = $empty;
        return $empty;
    }
    /** @return array<string, TemplateTypeVariance> */
    public function getVariances() : array
    {
        return $this->variances;
    }
    public function hasVariance(string $name) : bool
    {
        return array_key_exists($name, $this->getVariances());
    }
    public function getVariance(string $name) : ?\PHPStan\Type\Generic\TemplateTypeVariance
    {
        return $this->getVariances()[$name] ?? null;
    }
}
