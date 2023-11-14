<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
/** @api */
interface ReflectionProvider
{
    public function hasClass(string $className) : bool;
    public function getClass(string $className) : \PHPStan\Reflection\ClassReflection;
    public function getClassName(string $className) : string;
    public function supportsAnonymousClasses() : bool;
    public function getAnonymousClassReflection(Node\Stmt\Class_ $classNode, Scope $scope) : \PHPStan\Reflection\ClassReflection;
    public function hasFunction(Node\Name $nameNode, ?\PHPStan\Reflection\NamespaceAnswerer $namespaceAnswerer) : bool;
    public function getFunction(Node\Name $nameNode, ?\PHPStan\Reflection\NamespaceAnswerer $namespaceAnswerer) : \PHPStan\Reflection\FunctionReflection;
    public function resolveFunctionName(Node\Name $nameNode, ?\PHPStan\Reflection\NamespaceAnswerer $namespaceAnswerer) : ?string;
    public function hasConstant(Node\Name $nameNode, ?\PHPStan\Reflection\NamespaceAnswerer $namespaceAnswerer) : bool;
    public function getConstant(Node\Name $nameNode, ?\PHPStan\Reflection\NamespaceAnswerer $namespaceAnswerer) : \PHPStan\Reflection\GlobalConstantReflection;
    public function resolveConstantName(Node\Name $nameNode, ?\PHPStan\Reflection\NamespaceAnswerer $namespaceAnswerer) : ?string;
}
