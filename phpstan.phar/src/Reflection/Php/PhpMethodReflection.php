<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Php;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Namespace_;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionParameter;
use PHPStan\Cache\Cache;
use PHPStan\Parser\FunctionCallStatementFinder;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionVariantWithPhpDocs;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\MethodPrototypeReflection;
use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
use PHPStan\Type\VoidType;
use ReflectionException;
use function array_map;
use function explode;
use function filemtime;
use function in_array;
use function is_bool;
use function sprintf;
use function strtolower;
use function time;
use const PHP_VERSION_ID;
/** @api */
class PhpMethodReflection implements ExtendedMethodReflection
{
    /**
     * @var \PHPStan\Reflection\InitializerExprTypeResolver
     */
    private $initializerExprTypeResolver;
    /**
     * @var \PHPStan\Reflection\ClassReflection
     */
    private $declaringClass;
    /**
     * @var \PHPStan\Reflection\ClassReflection|null
     */
    private $declaringTrait;
    /**
     * @var \PHPStan\Reflection\Php\BuiltinMethodReflection
     */
    private $reflection;
    /**
     * @var \PHPStan\Reflection\ReflectionProvider
     */
    private $reflectionProvider;
    /**
     * @var \PHPStan\Parser\Parser
     */
    private $parser;
    /**
     * @var \PHPStan\Parser\FunctionCallStatementFinder
     */
    private $functionCallStatementFinder;
    /**
     * @var \PHPStan\Cache\Cache
     */
    private $cache;
    /**
     * @var \PHPStan\Type\Generic\TemplateTypeMap
     */
    private $templateTypeMap;
    /**
     * @var Type[]
     */
    private $phpDocParameterTypes;
    /**
     * @var \PHPStan\Type\Type|null
     */
    private $phpDocReturnType;
    /**
     * @var \PHPStan\Type\Type|null
     */
    private $phpDocThrowType;
    /**
     * @var string|null
     */
    private $deprecatedDescription;
    /**
     * @var bool
     */
    private $isDeprecated;
    /**
     * @var bool
     */
    private $isInternal;
    /**
     * @var bool
     */
    private $isFinal;
    /**
     * @var bool|null
     */
    private $isPure;
    /**
     * @var \PHPStan\Reflection\Assertions
     */
    private $asserts;
    /**
     * @var \PHPStan\Type\Type|null
     */
    private $selfOutType;
    /**
     * @var string|null
     */
    private $phpDocComment;
    /**
     * @var Type[]
     */
    private $phpDocParameterOutTypes;
    /** @var PhpParameterReflection[]|null */
    private $parameters;
    /**
     * @var \PHPStan\Type\Type|null
     */
    private $returnType;
    /**
     * @var \PHPStan\Type\Type|null
     */
    private $nativeReturnType;
    /** @var FunctionVariantWithPhpDocs[]|null */
    private $variants;
    /**
     * @param Type[] $phpDocParameterTypes
     * @param Type[] $phpDocParameterOutTypes
     */
    public function __construct(InitializerExprTypeResolver $initializerExprTypeResolver, ClassReflection $declaringClass, ?ClassReflection $declaringTrait, \PHPStan\Reflection\Php\BuiltinMethodReflection $reflection, ReflectionProvider $reflectionProvider, Parser $parser, FunctionCallStatementFinder $functionCallStatementFinder, Cache $cache, TemplateTypeMap $templateTypeMap, array $phpDocParameterTypes, ?Type $phpDocReturnType, ?Type $phpDocThrowType, ?string $deprecatedDescription, bool $isDeprecated, bool $isInternal, bool $isFinal, ?bool $isPure, Assertions $asserts, ?Type $selfOutType, ?string $phpDocComment, array $phpDocParameterOutTypes)
    {
        $this->initializerExprTypeResolver = $initializerExprTypeResolver;
        $this->declaringClass = $declaringClass;
        $this->declaringTrait = $declaringTrait;
        $this->reflection = $reflection;
        $this->reflectionProvider = $reflectionProvider;
        $this->parser = $parser;
        $this->functionCallStatementFinder = $functionCallStatementFinder;
        $this->cache = $cache;
        $this->templateTypeMap = $templateTypeMap;
        $this->phpDocParameterTypes = $phpDocParameterTypes;
        $this->phpDocReturnType = $phpDocReturnType;
        $this->phpDocThrowType = $phpDocThrowType;
        $this->deprecatedDescription = $deprecatedDescription;
        $this->isDeprecated = $isDeprecated;
        $this->isInternal = $isInternal;
        $this->isFinal = $isFinal;
        $this->isPure = $isPure;
        $this->asserts = $asserts;
        $this->selfOutType = $selfOutType;
        $this->phpDocComment = $phpDocComment;
        $this->phpDocParameterOutTypes = $phpDocParameterOutTypes;
    }
    public function getDeclaringClass() : ClassReflection
    {
        return $this->declaringClass;
    }
    public function getDeclaringTrait() : ?ClassReflection
    {
        return $this->declaringTrait;
    }
    /**
     * @return self|MethodPrototypeReflection
     */
    public function getPrototype() : ClassMemberReflection
    {
        try {
            $prototypeMethod = $this->reflection->getPrototype();
            $prototypeDeclaringClass = $this->declaringClass->getAncestorWithClassName($prototypeMethod->getDeclaringClass()->getName());
            if ($prototypeDeclaringClass === null) {
                $prototypeDeclaringClass = $this->reflectionProvider->getClass($prototypeMethod->getDeclaringClass()->getName());
            }
            $tentativeReturnType = null;
            if ($prototypeMethod->getTentativeReturnType() !== null) {
                $tentativeReturnType = TypehintHelper::decideTypeFromReflection($prototypeMethod->getTentativeReturnType());
            }
            return new MethodPrototypeReflection($prototypeMethod->getName(), $prototypeDeclaringClass, $prototypeMethod->isStatic(), $prototypeMethod->isPrivate(), $prototypeMethod->isPublic(), $prototypeMethod->isAbstract(), $prototypeMethod->isFinal(), $prototypeDeclaringClass->getNativeMethod($prototypeMethod->getName())->getVariants(), $tentativeReturnType);
        } catch (ReflectionException $exception) {
            return $this;
        }
    }
    public function isStatic() : bool
    {
        return $this->reflection->isStatic();
    }
    public function getName() : string
    {
        $name = $this->reflection->getName();
        $lowercaseName = strtolower($name);
        if ($lowercaseName === $name) {
            if (PHP_VERSION_ID >= 80000) {
                return $name;
            }
            // fix for https://bugs.php.net/bug.php?id=74939
            foreach ($this->getDeclaringClass()->getNativeReflection()->getTraitAliases() as $traitTarget) {
                $correctName = $this->getMethodNameWithCorrectCase($name, $traitTarget);
                if ($correctName !== null) {
                    $name = $correctName;
                    break;
                }
            }
        }
        return $name;
    }
    private function getMethodNameWithCorrectCase(string $lowercaseMethodName, string $traitTarget) : ?string
    {
        $trait = explode('::', $traitTarget)[0];
        $traitReflection = $this->reflectionProvider->getClass($trait)->getNativeReflection();
        foreach ($traitReflection->getTraitAliases() as $methodAlias => $aliasTraitTarget) {
            if ($lowercaseMethodName === strtolower($methodAlias)) {
                return $methodAlias;
            }
            $correctName = $this->getMethodNameWithCorrectCase($lowercaseMethodName, $aliasTraitTarget);
            if ($correctName !== null) {
                return $correctName;
            }
        }
        return null;
    }
    /**
     * @return ParametersAcceptorWithPhpDocs[]
     */
    public function getVariants() : array
    {
        if ($this->variants === null) {
            $this->variants = [new FunctionVariantWithPhpDocs($this->templateTypeMap, null, $this->getParameters(), $this->isVariadic(), $this->getReturnType(), $this->getPhpDocReturnType(), $this->getNativeReturnType())];
        }
        return $this->variants;
    }
    /**
     * @return ParameterReflectionWithPhpDocs[]
     */
    private function getParameters() : array
    {
        if ($this->parameters === null) {
            $this->parameters = array_map(function (ReflectionParameter $reflection) : \PHPStan\Reflection\Php\PhpParameterReflection {
                return new \PHPStan\Reflection\Php\PhpParameterReflection($this->initializerExprTypeResolver, $reflection, $this->phpDocParameterTypes[$reflection->getName()] ?? null, $this->getDeclaringClass()->getName(), $this->phpDocParameterOutTypes[$reflection->getName()] ?? null);
            }, $this->reflection->getParameters());
        }
        return $this->parameters;
    }
    private function isVariadic() : bool
    {
        $isNativelyVariadic = $this->reflection->isVariadic();
        $declaringClass = $this->declaringClass;
        $filename = $this->declaringClass->getFileName();
        if ($this->declaringTrait !== null) {
            $declaringClass = $this->declaringTrait;
            $filename = $this->declaringTrait->getFileName();
        }
        if (!$isNativelyVariadic && $filename !== null) {
            $modifiedTime = @filemtime($filename);
            if ($modifiedTime === \false) {
                $modifiedTime = time();
            }
            $key = sprintf('variadic-method-%s-%s-%s', $declaringClass->getName(), $this->reflection->getName(), $filename);
            $variableCacheKey = sprintf('%d-v4', $modifiedTime);
            $cachedResult = $this->cache->load($key, $variableCacheKey);
            if ($cachedResult === null || !is_bool($cachedResult)) {
                $nodes = $this->parser->parseFile($filename);
                $result = $this->callsFuncGetArgs($declaringClass, $nodes);
                $this->cache->save($key, $variableCacheKey, $result);
                return $result;
            }
            return $cachedResult;
        }
        return $isNativelyVariadic;
    }
    /**
     * @param Node[] $nodes
     */
    private function callsFuncGetArgs(ClassReflection $declaringClass, array $nodes) : bool
    {
        foreach ($nodes as $node) {
            if ($node instanceof Node\Stmt\ClassLike) {
                if (!isset($node->namespacedName)) {
                    continue;
                }
                if ($declaringClass->getName() !== (string) $node->namespacedName) {
                    continue;
                }
                if ($this->callsFuncGetArgs($declaringClass, $node->stmts)) {
                    return \true;
                }
                continue;
            }
            if ($node instanceof ClassMethod) {
                if ($node->getStmts() === null) {
                    continue;
                    // interface
                }
                $methodName = $node->name->name;
                if ($methodName === $this->reflection->getName()) {
                    return $this->functionCallStatementFinder->findFunctionCallInStatements(ParametersAcceptor::VARIADIC_FUNCTIONS, $node->getStmts()) !== null;
                }
                continue;
            }
            if ($node instanceof Function_) {
                continue;
            }
            if ($node instanceof Namespace_) {
                if ($this->callsFuncGetArgs($declaringClass, $node->stmts)) {
                    return \true;
                }
                continue;
            }
            if (!$node instanceof Declare_ || $node->stmts === null) {
                continue;
            }
            if ($this->callsFuncGetArgs($declaringClass, $node->stmts)) {
                return \true;
            }
        }
        return \false;
    }
    public function isPrivate() : bool
    {
        return $this->reflection->isPrivate();
    }
    public function isPublic() : bool
    {
        return $this->reflection->isPublic();
    }
    private function getReturnType() : Type
    {
        if ($this->returnType === null) {
            $name = strtolower($this->getName());
            $returnType = $this->reflection->getReturnType();
            if ($returnType === null) {
                if (in_array($name, ['__construct', '__destruct', '__unset', '__wakeup', '__clone'], \true)) {
                    return $this->returnType = TypehintHelper::decideType(new VoidType(), $this->phpDocReturnType);
                }
                if ($name === '__tostring') {
                    return $this->returnType = TypehintHelper::decideType(new StringType(), $this->phpDocReturnType);
                }
                if ($name === '__isset') {
                    return $this->returnType = TypehintHelper::decideType(new BooleanType(), $this->phpDocReturnType);
                }
                if ($name === '__sleep') {
                    return $this->returnType = TypehintHelper::decideType(new ArrayType(new IntegerType(), new StringType()), $this->phpDocReturnType);
                }
                if ($name === '__set_state') {
                    return $this->returnType = TypehintHelper::decideType(new ObjectWithoutClassType(), $this->phpDocReturnType);
                }
            }
            $this->returnType = TypehintHelper::decideTypeFromReflection($returnType, $this->phpDocReturnType, $this->declaringClass);
        }
        return $this->returnType;
    }
    private function getPhpDocReturnType() : Type
    {
        if ($this->phpDocReturnType !== null) {
            return $this->phpDocReturnType;
        }
        return new MixedType();
    }
    private function getNativeReturnType() : Type
    {
        if ($this->nativeReturnType === null) {
            $this->nativeReturnType = TypehintHelper::decideTypeFromReflection($this->reflection->getReturnType(), null, $this->declaringClass);
        }
        return $this->nativeReturnType;
    }
    public function getDeprecatedDescription() : ?string
    {
        if ($this->isDeprecated) {
            return $this->deprecatedDescription;
        }
        return null;
    }
    public function isDeprecated() : TrinaryLogic
    {
        if ($this->isDeprecated) {
            return TrinaryLogic::createYes();
        }
        return $this->reflection->isDeprecated();
    }
    public function isInternal() : TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->isInternal || $this->reflection->isInternal());
    }
    public function isFinal() : TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->isFinal || $this->reflection->isFinal());
    }
    public function isAbstract() : bool
    {
        return $this->reflection->isAbstract();
    }
    public function getThrowType() : ?Type
    {
        return $this->phpDocThrowType;
    }
    public function hasSideEffects() : TrinaryLogic
    {
        if (strtolower($this->getName()) !== '__construct' && $this->getReturnType()->isVoid()->yes()) {
            return TrinaryLogic::createYes();
        }
        if ($this->isPure !== null) {
            return TrinaryLogic::createFromBoolean(!$this->isPure);
        }
        return TrinaryLogic::createMaybe();
    }
    public function getAsserts() : Assertions
    {
        return $this->asserts;
    }
    public function getSelfOutType() : ?Type
    {
        return $this->selfOutType;
    }
    public function getDocComment() : ?string
    {
        return $this->phpDocComment;
    }
    public function returnsByReference() : TrinaryLogic
    {
        return $this->reflection->returnsByReference();
    }
}
