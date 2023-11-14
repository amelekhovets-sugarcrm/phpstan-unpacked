<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PHPStan\Php\PhpVersion;
use PHPStan\ShouldNotHappenException;
class PhpVersionStaticAccessor
{
    /**
     * @var \PHPStan\Php\PhpVersion|null
     */
    private static $instance;
    private function __construct()
    {
    }
    public static function registerInstance(PhpVersion $phpVersion) : void
    {
        self::$instance = $phpVersion;
    }
    public static function getInstance() : PhpVersion
    {
        if (self::$instance === null) {
            throw new ShouldNotHappenException();
        }
        return self::$instance;
    }
}
