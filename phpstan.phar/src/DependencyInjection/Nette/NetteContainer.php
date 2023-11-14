<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection\Nette;

use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ParameterNotFoundException;
use function array_key_exists;
use function array_keys;
use function array_map;
/**
 * @internal
 */
class NetteContainer implements Container
{
    /**
     * @var \Nette\DI\Container
     */
    private $container;
    public function __construct(\_PHPStan_c6b09fbdf\Nette\DI\Container $container)
    {
        $this->container = $container;
    }
    public function hasService(string $serviceName) : bool
    {
        return $this->container->hasService($serviceName);
    }
    /**
     * @return mixed
     */
    public function getService(string $serviceName)
    {
        return $this->container->getService($serviceName);
    }
    /**
     * @phpstan-template T of object
     * @phpstan-param class-string<T> $className
     * @phpstan-return T
     * @return mixed
     */
    public function getByType(string $className)
    {
        return $this->container->getByType($className);
    }
    /**
     * @param class-string $className
     * @return string[]
     */
    public function findServiceNamesByType(string $className) : array
    {
        return $this->container->findByType($className);
    }
    /**
     * @return mixed[]
     */
    public function getServicesByTag(string $tagName) : array
    {
        return $this->tagsToServices($this->container->findByTag($tagName));
    }
    /**
     * @return mixed[]
     */
    public function getParameters() : array
    {
        return $this->container->getParameters();
    }
    public function hasParameter(string $parameterName) : bool
    {
        return array_key_exists($parameterName, $this->container->getParameters());
    }
    /**
     * @return mixed
     */
    public function getParameter(string $parameterName)
    {
        if (!$this->hasParameter($parameterName)) {
            throw new ParameterNotFoundException($parameterName);
        }
        return $this->container->getParameter($parameterName);
    }
    /**
     * @param mixed[] $tags
     * @return mixed[]
     */
    private function tagsToServices(array $tags) : array
    {
        return array_map(function (string $serviceName) {
            return $this->getService($serviceName);
        }, array_keys($tags));
    }
}
