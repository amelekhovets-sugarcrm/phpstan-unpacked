<?php

declare (strict_types=1);
namespace PHPStan\Rules\Constants;

use PHPStan\DependencyInjection\Container;
class LazyAlwaysUsedClassConstantsExtensionProvider implements \PHPStan\Rules\Constants\AlwaysUsedClassConstantsExtensionProvider
{
    /**
     * @var \PHPStan\DependencyInjection\Container
     */
    private $container;
    /** @var AlwaysUsedClassConstantsExtension[]|null */
    private $extensions;
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    public function getExtensions() : array
    {
        if ($this->extensions === null) {
            $this->extensions = $this->container->getServicesByTag(\PHPStan\Rules\Constants\AlwaysUsedClassConstantsExtensionProvider::EXTENSION_TAG);
        }
        return $this->extensions;
    }
}
