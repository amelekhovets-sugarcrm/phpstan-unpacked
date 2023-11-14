<?php

declare (strict_types=1);
namespace PHPStan\Broker;

use PHPStan\AnalysedCodeException;
use function sprintf;
class ClassNotFoundException extends AnalysedCodeException
{
    /**
     * @var string
     */
    private $className;
    public function __construct(string $className)
    {
        $this->className = $className;
        parent::__construct(sprintf('Class %s was not found while trying to analyse it - discovering symbols is probably not configured properly.', $className));
    }
    public function getClassName() : string
    {
        return $this->className;
    }
    public function getTip() : ?string
    {
        return 'Learn more at https://phpstan.org/user-guide/discovering-symbols';
    }
}
