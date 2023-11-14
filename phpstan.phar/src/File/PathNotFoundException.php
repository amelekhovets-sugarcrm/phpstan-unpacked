<?php

declare (strict_types=1);
namespace PHPStan\File;

use Exception;
use function sprintf;
class PathNotFoundException extends Exception
{
    /**
     * @var string
     */
    private $path;
    public function __construct(string $path)
    {
        $this->path = $path;
        parent::__construct(sprintf('Path %s does not exist', $path));
    }
    public function getPath() : string
    {
        return $this->path;
    }
}
