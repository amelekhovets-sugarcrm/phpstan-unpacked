<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Broker\Broker;
use PHPStan\Reflection\BrokerAwareExtension;
use function array_filter;
use function array_values;
class OperatorTypeSpecifyingExtensionRegistry
{
    /**
     * @var OperatorTypeSpecifyingExtension[]
     */
    private $extensions;
    /**
     * @param OperatorTypeSpecifyingExtension[] $extensions
     */
    public function __construct(?Broker $broker, array $extensions)
    {
        $this->extensions = $extensions;
        if ($broker === null) {
            return;
        }
        foreach ($extensions as $extension) {
            if (!$extension instanceof BrokerAwareExtension) {
                continue;
            }
            $extension->setBroker($broker);
        }
    }
    /**
     * @return OperatorTypeSpecifyingExtension[]
     */
    public function getOperatorTypeSpecifyingExtensions(string $operator, \PHPStan\Type\Type $leftType, \PHPStan\Type\Type $rightType) : array
    {
        return array_values(array_filter($this->extensions, static function (\PHPStan\Type\OperatorTypeSpecifyingExtension $extension) use($operator, $leftType, $rightType) : bool {
            return $extension->isOperatorSupported($operator, $leftType, $rightType);
        }));
    }
}
