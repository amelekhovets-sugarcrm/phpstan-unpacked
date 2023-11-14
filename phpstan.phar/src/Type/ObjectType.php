<?php

declare (strict_types=1);
namespace PHPStan\Type;

use ArrayAccess;
use Closure;
use Countable;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Error;
use Exception;
use Iterator;
use IteratorAggregate;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Broker\Broker;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\Php\UniversalObjectCratesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\Reflection\Type\CalledOnTypeUnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\CalledOnTypeUnresolvedPropertyPrototypeReflection;
use PHPStan\Reflection\Type\UnionTypeUnresolvedPropertyPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Traits\MaybeIterableTypeTrait;
use PHPStan\Type\Traits\NonArrayTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonTypeTrait;
use Throwable;
use Traversable;
use function array_key_exists;
use function array_map;
use function array_values;
use function count;
use function implode;
use function in_array;
use function sprintf;
use function strtolower;
/** @api */
class ObjectType implements \PHPStan\Type\TypeWithClassName, \PHPStan\Type\SubtractableType
{
    /**
     * @var string
     */
    private $className;
    /**
     * @var \PHPStan\Reflection\ClassReflection|null
     */
    private $classReflection;
    use MaybeIterableTypeTrait;
    use NonArrayTypeTrait;
    use NonGenericTypeTrait;
    use UndecidedComparisonTypeTrait;
    use NonGeneralizableTypeTrait;
    private const EXTRA_OFFSET_CLASSES = ['SimpleXMLElement', 'DOMNodeList', 'Threaded'];
    /**
     * @var \PHPStan\Type\Type|null
     */
    private $subtractedType;
    /** @var array<string, array<string, TrinaryLogic>> */
    private static $superTypes = [];
    /**
     * @var \PHPStan\Type\ObjectType|null
     */
    private $cachedParent;
    /** @var self[]|null */
    private $cachedInterfaces;
    /** @var array<string, array<string, array<string, UnresolvedMethodPrototypeReflection>>> */
    private static $methods = [];
    /** @var array<string, array<string, array<string, UnresolvedPropertyPrototypeReflection>>> */
    private static $properties = [];
    /** @var array<string, array<string, self|null>> */
    private static $ancestors = [];
    /** @var array<string, self|null> */
    private $currentAncestors = [];
    /**
     * @var string|null
     */
    private $cachedDescription;
    /** @api */
    public function __construct(string $className, ?\PHPStan\Type\Type $subtractedType = null, ?ClassReflection $classReflection = null)
    {
        $this->className = $className;
        $this->classReflection = $classReflection;
        if ($subtractedType instanceof \PHPStan\Type\NeverType) {
            $subtractedType = null;
        }
        $this->subtractedType = $subtractedType;
    }
    public static function resetCaches() : void
    {
        self::$superTypes = [];
        self::$methods = [];
        self::$properties = [];
        self::$ancestors = [];
    }
    private static function createFromReflection(ClassReflection $reflection) : self
    {
        if (!$reflection->isGeneric()) {
            return new \PHPStan\Type\ObjectType($reflection->getName());
        }
        return new GenericObjectType($reflection->getName(), $reflection->typeMapToList($reflection->getActiveTemplateTypeMap()), null, null, $reflection->varianceMapToList($reflection->getCallSiteVarianceMap()));
    }
    public function getClassName() : string
    {
        return $this->className;
    }
    public function hasProperty(string $propertyName) : TrinaryLogic
    {
        $classReflection = $this->getClassReflection();
        if ($classReflection === null) {
            return TrinaryLogic::createMaybe();
        }
        if ($classReflection->hasProperty($propertyName)) {
            return TrinaryLogic::createYes();
        }
        if ($classReflection->allowsDynamicProperties()) {
            return TrinaryLogic::createMaybe();
        }
        if (!$classReflection->isFinal()) {
            return TrinaryLogic::createMaybe();
        }
        return TrinaryLogic::createNo();
    }
    public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope) : PropertyReflection
    {
        return $this->getUnresolvedPropertyPrototype($propertyName, $scope)->getTransformedProperty();
    }
    public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope) : UnresolvedPropertyPrototypeReflection
    {
        if (!$scope->isInClass()) {
            $canAccessProperty = 'no';
        } else {
            $canAccessProperty = $scope->getClassReflection()->getName();
        }
        $description = $this->describeCache();
        if (isset(self::$properties[$description][$propertyName][$canAccessProperty])) {
            return self::$properties[$description][$propertyName][$canAccessProperty];
        }
        $nakedClassReflection = $this->getNakedClassReflection();
        if ($nakedClassReflection === null) {
            throw new ClassNotFoundException($this->className);
        }
        if ($nakedClassReflection->isEnum()) {
            if ($propertyName === 'name' || $propertyName === 'value' && $nakedClassReflection->isBackedEnum()) {
                $properties = [];
                foreach ($this->getEnumCases() as $enumCase) {
                    $properties[] = $enumCase->getUnresolvedPropertyPrototype($propertyName, $scope);
                }
                if (count($properties) > 0) {
                    if (count($properties) === 1) {
                        return $properties[0];
                    }
                    return new UnionTypeUnresolvedPropertyPrototypeReflection($propertyName, $properties);
                }
            }
        }
        if (!$nakedClassReflection->hasNativeProperty($propertyName)) {
            $nakedClassReflection = $this->getClassReflection();
        }
        if ($nakedClassReflection === null) {
            throw new ClassNotFoundException($this->className);
        }
        $property = $nakedClassReflection->getProperty($propertyName, $scope);
        $ancestor = $this->getAncestorWithClassName($property->getDeclaringClass()->getName());
        $resolvedClassReflection = null;
        if ($ancestor !== null && $ancestor->hasProperty($propertyName)->yes()) {
            $resolvedClassReflection = $ancestor->getClassReflection();
            if ($ancestor !== $this) {
                $property = $ancestor->getUnresolvedPropertyPrototype($propertyName, $scope)->getNakedProperty();
            }
        }
        if ($resolvedClassReflection === null) {
            $resolvedClassReflection = $property->getDeclaringClass();
        }
        return self::$properties[$description][$propertyName][$canAccessProperty] = new CalledOnTypeUnresolvedPropertyPrototypeReflection($property, $resolvedClassReflection, \true, $this);
    }
    public function getPropertyWithoutTransformingStatic(string $propertyName, ClassMemberAccessAnswerer $scope) : PropertyReflection
    {
        $classReflection = $this->getNakedClassReflection();
        if ($classReflection === null) {
            throw new ClassNotFoundException($this->className);
        }
        if (!$classReflection->hasProperty($propertyName)) {
            $classReflection = $this->getClassReflection();
        }
        if ($classReflection === null) {
            throw new ClassNotFoundException($this->className);
        }
        return $classReflection->getProperty($propertyName, $scope);
    }
    /**
     * @return string[]
     */
    public function getReferencedClasses() : array
    {
        return [$this->className];
    }
    public function getObjectClassNames() : array
    {
        return [$this->className];
    }
    public function getObjectClassReflections() : array
    {
        $classReflection = $this->getClassReflection();
        if ($classReflection === null) {
            return [];
        }
        return [$classReflection];
    }
    public function accepts(\PHPStan\Type\Type $type, bool $strictTypes) : TrinaryLogic
    {
        return $this->acceptsWithReason($type, $strictTypes)->result;
    }
    public function acceptsWithReason(\PHPStan\Type\Type $type, bool $strictTypes) : \PHPStan\Type\AcceptsResult
    {
        if ($type instanceof \PHPStan\Type\StaticType) {
            return $this->checkSubclassAcceptability($type->getClassName());
        }
        if ($type instanceof \PHPStan\Type\CompoundType) {
            return $type->isAcceptedWithReasonBy($this, $strictTypes);
        }
        if ($type instanceof \PHPStan\Type\ClosureType) {
            return new \PHPStan\Type\AcceptsResult($this->isInstanceOf(Closure::class), []);
        }
        if ($type instanceof \PHPStan\Type\ObjectWithoutClassType) {
            return \PHPStan\Type\AcceptsResult::createMaybe();
        }
        $thatClassNames = $type->getObjectClassNames();
        if (count($thatClassNames) > 1) {
            throw new ShouldNotHappenException();
        }
        if ($thatClassNames === []) {
            return \PHPStan\Type\AcceptsResult::createNo();
        }
        return $this->checkSubclassAcceptability($thatClassNames[0]);
    }
    public function isSuperTypeOf(\PHPStan\Type\Type $type) : TrinaryLogic
    {
        $thatClassNames = $type->getObjectClassNames();
        if (!$type instanceof \PHPStan\Type\CompoundType && $thatClassNames === [] && !$type instanceof \PHPStan\Type\ObjectWithoutClassType) {
            return TrinaryLogic::createNo();
        }
        $thisDescription = $this->describeCache();
        if ($type instanceof self) {
            $description = $type->describeCache();
        } else {
            $description = $type->describe(\PHPStan\Type\VerbosityLevel::cache());
        }
        if (isset(self::$superTypes[$thisDescription][$description])) {
            return self::$superTypes[$thisDescription][$description];
        }
        if ($type instanceof \PHPStan\Type\CompoundType) {
            return self::$superTypes[$thisDescription][$description] = $type->isSubTypeOf($this);
        }
        if ($type instanceof \PHPStan\Type\ClosureType) {
            return self::$superTypes[$thisDescription][$description] = $this->isInstanceOf(Closure::class);
        }
        if ($type instanceof \PHPStan\Type\ObjectWithoutClassType) {
            if ($type->getSubtractedType() !== null) {
                $isSuperType = $type->getSubtractedType()->isSuperTypeOf($this);
                if ($isSuperType->yes()) {
                    return self::$superTypes[$thisDescription][$description] = TrinaryLogic::createNo();
                }
            }
            return self::$superTypes[$thisDescription][$description] = TrinaryLogic::createMaybe();
        }
        $transformResult = static function (TrinaryLogic $result) {
            return $result;
        };
        if ($this->subtractedType !== null) {
            $isSuperType = $this->subtractedType->isSuperTypeOf($type);
            if ($isSuperType->yes()) {
                return self::$superTypes[$thisDescription][$description] = TrinaryLogic::createNo();
            }
            if ($isSuperType->maybe()) {
                $transformResult = static function (TrinaryLogic $result) {
                    return $result->and(TrinaryLogic::createMaybe());
                };
            }
        }
        if ($type instanceof \PHPStan\Type\SubtractableType && $type->getSubtractedType() !== null) {
            $isSuperType = $type->getSubtractedType()->isSuperTypeOf($this);
            if ($isSuperType->yes()) {
                return self::$superTypes[$thisDescription][$description] = TrinaryLogic::createNo();
            }
        }
        $thisClassName = $this->className;
        if (count($thatClassNames) > 1) {
            throw new ShouldNotHappenException();
        }
        if ($thatClassNames[0] === $thisClassName) {
            return $transformResult(TrinaryLogic::createYes());
        }
        $reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
        if ($this->getClassReflection() === null || !$reflectionProvider->hasClass($thatClassNames[0])) {
            return self::$superTypes[$thisDescription][$description] = TrinaryLogic::createMaybe();
        }
        $thisClassReflection = $this->getClassReflection();
        $thatClassReflection = $reflectionProvider->getClass($thatClassNames[0]);
        if ($thisClassReflection->isTrait() || $thatClassReflection->isTrait()) {
            return TrinaryLogic::createNo();
        }
        if ($thisClassReflection->getName() === $thatClassReflection->getName()) {
            return self::$superTypes[$thisDescription][$description] = $transformResult(TrinaryLogic::createYes());
        }
        if ($thatClassReflection->isSubclassOf($thisClassName)) {
            return self::$superTypes[$thisDescription][$description] = $transformResult(TrinaryLogic::createYes());
        }
        if ($thisClassReflection->isSubclassOf($thatClassNames[0])) {
            return self::$superTypes[$thisDescription][$description] = TrinaryLogic::createMaybe();
        }
        if ($thisClassReflection->isInterface() && !$thatClassReflection->getNativeReflection()->isFinal()) {
            return self::$superTypes[$thisDescription][$description] = TrinaryLogic::createMaybe();
        }
        if ($thatClassReflection->isInterface() && !$thisClassReflection->getNativeReflection()->isFinal()) {
            return self::$superTypes[$thisDescription][$description] = TrinaryLogic::createMaybe();
        }
        return self::$superTypes[$thisDescription][$description] = TrinaryLogic::createNo();
    }
    public function equals(\PHPStan\Type\Type $type) : bool
    {
        if (!$type instanceof self) {
            return \false;
        }
        if ($type instanceof EnumCaseObjectType) {
            return \false;
        }
        if ($this->className !== $type->className) {
            return \false;
        }
        if ($this->subtractedType === null) {
            return $type->subtractedType === null;
        }
        if ($type->subtractedType === null) {
            return \false;
        }
        return $this->subtractedType->equals($type->subtractedType);
    }
    private function checkSubclassAcceptability(string $thatClass) : \PHPStan\Type\AcceptsResult
    {
        if ($this->className === $thatClass) {
            return \PHPStan\Type\AcceptsResult::createYes();
        }
        $reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
        if ($this->getClassReflection() === null || !$reflectionProvider->hasClass($thatClass)) {
            return \PHPStan\Type\AcceptsResult::createNo();
        }
        $thisReflection = $this->getClassReflection();
        $thatReflection = $reflectionProvider->getClass($thatClass);
        if ($thisReflection->getName() === $thatReflection->getName()) {
            // class alias
            return \PHPStan\Type\AcceptsResult::createYes();
        }
        if ($thisReflection->isInterface() && $thatReflection->isInterface()) {
            return \PHPStan\Type\AcceptsResult::createFromBoolean($thatReflection->implementsInterface($thisReflection->getName()));
        }
        return \PHPStan\Type\AcceptsResult::createFromBoolean($thatReflection->isSubclassOf($thisReflection->getName()));
    }
    public function describe(\PHPStan\Type\VerbosityLevel $level) : string
    {
        $preciseNameCallback = function () : string {
            $reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
            if (!$reflectionProvider->hasClass($this->className)) {
                return $this->className;
            }
            return $reflectionProvider->getClassName($this->className);
        };
        $preciseWithSubtracted = function () use($level) : string {
            $description = $this->className;
            if ($this->subtractedType !== null) {
                $description .= sprintf('~%s', $this->subtractedType->describe($level));
            }
            return $description;
        };
        return $level->handle($preciseNameCallback, $preciseNameCallback, $preciseWithSubtracted, function () use($preciseWithSubtracted) : string {
            $reflection = $this->classReflection;
            $line = '';
            if ($reflection !== null) {
                $line .= '-';
                $line .= (string) $reflection->getNativeReflection()->getStartLine();
                $line .= '-';
            }
            return $preciseWithSubtracted() . '-' . static::class . '-' . $line . $this->describeAdditionalCacheKey();
        });
    }
    protected function describeAdditionalCacheKey() : string
    {
        return '';
    }
    private function describeCache() : string
    {
        if ($this->cachedDescription !== null) {
            return $this->cachedDescription;
        }
        if (static::class !== self::class) {
            return $this->cachedDescription = $this->describe(\PHPStan\Type\VerbosityLevel::cache());
        }
        $description = $this->className;
        if ($this instanceof GenericObjectType) {
            $description .= '<';
            $typeDescriptions = [];
            foreach ($this->getTypes() as $type) {
                $typeDescriptions[] = $type->describe(\PHPStan\Type\VerbosityLevel::cache());
            }
            $description .= '<' . implode(', ', $typeDescriptions) . '>';
        }
        if ($this->subtractedType !== null) {
            $description .= sprintf('~%s', $this->subtractedType->describe(\PHPStan\Type\VerbosityLevel::cache()));
        }
        $reflection = $this->classReflection;
        if ($reflection !== null) {
            $description .= '-';
            $description .= (string) $reflection->getNativeReflection()->getStartLine();
            $description .= '-';
        }
        return $this->cachedDescription = $description;
    }
    public function toNumber() : \PHPStan\Type\Type
    {
        if ($this->isInstanceOf('SimpleXMLElement')->yes()) {
            return new \PHPStan\Type\UnionType([new \PHPStan\Type\FloatType(), new \PHPStan\Type\IntegerType()]);
        }
        return new \PHPStan\Type\ErrorType();
    }
    public function toInteger() : \PHPStan\Type\Type
    {
        if ($this->isInstanceOf('SimpleXMLElement')->yes()) {
            return new \PHPStan\Type\IntegerType();
        }
        if (in_array($this->getClassName(), ['CurlHandle', 'CurlMultiHandle'], \true)) {
            return new \PHPStan\Type\IntegerType();
        }
        return new \PHPStan\Type\ErrorType();
    }
    public function toFloat() : \PHPStan\Type\Type
    {
        if ($this->isInstanceOf('SimpleXMLElement')->yes()) {
            return new \PHPStan\Type\FloatType();
        }
        return new \PHPStan\Type\ErrorType();
    }
    public function toString() : \PHPStan\Type\Type
    {
        $classReflection = $this->getClassReflection();
        if ($classReflection === null) {
            return new \PHPStan\Type\ErrorType();
        }
        if ($classReflection->hasNativeMethod('__toString')) {
            return ParametersAcceptorSelector::selectSingle($this->getMethod('__toString', new OutOfClassScope())->getVariants())->getReturnType();
        }
        return new \PHPStan\Type\ErrorType();
    }
    public function toArray() : \PHPStan\Type\Type
    {
        $classReflection = $this->getClassReflection();
        if ($classReflection === null) {
            return new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType());
        }
        $reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
        if (!$classReflection->getNativeReflection()->isUserDefined() || UniversalObjectCratesClassReflectionExtension::isUniversalObjectCrate($reflectionProvider, Broker::getInstance()->getUniversalObjectCratesClasses(), $classReflection)) {
            return new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType());
        }
        $arrayKeys = [];
        $arrayValues = [];
        $isFinal = $classReflection->isFinal();
        do {
            foreach ($classReflection->getNativeReflection()->getProperties() as $nativeProperty) {
                if ($nativeProperty->isStatic()) {
                    continue;
                }
                $declaringClass = $reflectionProvider->getClass($nativeProperty->getDeclaringClass()->getName());
                $property = $declaringClass->getNativeProperty($nativeProperty->getName());
                $keyName = $nativeProperty->getName();
                if ($nativeProperty->isPrivate()) {
                    $keyName = sprintf("\x00%s\x00%s", $declaringClass->getName(), $keyName);
                } elseif ($nativeProperty->isProtected()) {
                    $keyName = sprintf("\x00*\x00%s", $keyName);
                }
                $arrayKeys[] = new ConstantStringType($keyName);
                $arrayValues[] = $property->getReadableType();
            }
            $classReflection = $classReflection->getParentClass();
        } while ($classReflection !== null);
        if (!$isFinal && count($arrayKeys) === 0) {
            return new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType());
        }
        return new ConstantArrayType($arrayKeys, $arrayValues);
    }
    public function toArrayKey() : \PHPStan\Type\Type
    {
        return $this->toString();
    }
    public function toBoolean() : \PHPStan\Type\BooleanType
    {
        if ($this->isInstanceOf('SimpleXMLElement')->yes()) {
            return new \PHPStan\Type\BooleanType();
        }
        return new ConstantBooleanType(\true);
    }
    public function isObject() : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function isEnum() : TrinaryLogic
    {
        $classReflection = $this->getClassReflection();
        if ($classReflection === null) {
            return TrinaryLogic::createMaybe();
        }
        return TrinaryLogic::createFromBoolean($classReflection->isEnum());
    }
    public function canAccessProperties() : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function canCallMethods() : TrinaryLogic
    {
        if (strtolower($this->className) === 'stdclass') {
            return TrinaryLogic::createNo();
        }
        return TrinaryLogic::createYes();
    }
    public function hasMethod(string $methodName) : TrinaryLogic
    {
        $classReflection = $this->getClassReflection();
        if ($classReflection === null) {
            return TrinaryLogic::createMaybe();
        }
        if ($classReflection->hasMethod($methodName)) {
            return TrinaryLogic::createYes();
        }
        if ($classReflection->isFinal()) {
            return TrinaryLogic::createNo();
        }
        return TrinaryLogic::createMaybe();
    }
    public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope) : ExtendedMethodReflection
    {
        return $this->getUnresolvedMethodPrototype($methodName, $scope)->getTransformedMethod();
    }
    public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope) : UnresolvedMethodPrototypeReflection
    {
        if (!$scope->isInClass()) {
            $canCallMethod = 'no';
        } else {
            $canCallMethod = $scope->getClassReflection()->getName();
        }
        $description = $this->describeCache();
        if (isset(self::$methods[$description][$methodName][$canCallMethod])) {
            return self::$methods[$description][$methodName][$canCallMethod];
        }
        $nakedClassReflection = $this->getNakedClassReflection();
        if ($nakedClassReflection === null) {
            throw new ClassNotFoundException($this->className);
        }
        if (!$nakedClassReflection->hasNativeMethod($methodName)) {
            $nakedClassReflection = $this->getClassReflection();
        }
        if ($nakedClassReflection === null) {
            throw new ClassNotFoundException($this->className);
        }
        $method = $nakedClassReflection->getMethod($methodName, $scope);
        $ancestor = $this->getAncestorWithClassName($method->getDeclaringClass()->getName());
        $resolvedClassReflection = null;
        if ($ancestor !== null) {
            $resolvedClassReflection = $ancestor->getClassReflection();
            if ($ancestor !== $this) {
                $method = $ancestor->getUnresolvedMethodPrototype($methodName, $scope)->getNakedMethod();
            }
        }
        if ($resolvedClassReflection === null) {
            $resolvedClassReflection = $method->getDeclaringClass();
        }
        return self::$methods[$description][$methodName][$canCallMethod] = new CalledOnTypeUnresolvedMethodPrototypeReflection($method, $resolvedClassReflection, \true, $this);
    }
    public function canAccessConstants() : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function hasConstant(string $constantName) : TrinaryLogic
    {
        $class = $this->getClassReflection();
        if ($class === null) {
            return TrinaryLogic::createNo();
        }
        return TrinaryLogic::createFromBoolean($class->hasConstant($constantName));
    }
    public function getConstant(string $constantName) : ConstantReflection
    {
        $class = $this->getClassReflection();
        if ($class === null) {
            throw new ClassNotFoundException($this->className);
        }
        return $class->getConstant($constantName);
    }
    public function getTemplateType(string $ancestorClassName, string $templateTypeName) : \PHPStan\Type\Type
    {
        $classReflection = $this->getClassReflection();
        if ($classReflection === null) {
            return new \PHPStan\Type\ErrorType();
        }
        $ancestorClassReflection = $classReflection->getAncestorWithClassName($ancestorClassName);
        if ($ancestorClassReflection === null) {
            return new \PHPStan\Type\ErrorType();
        }
        $activeTemplateTypeMap = $ancestorClassReflection->getPossiblyIncompleteActiveTemplateTypeMap();
        $type = $activeTemplateTypeMap->getType($templateTypeName);
        if ($type === null) {
            return new \PHPStan\Type\ErrorType();
        }
        if ($type instanceof \PHPStan\Type\ErrorType) {
            $templateTypeMap = $ancestorClassReflection->getTemplateTypeMap();
            $templateType = $templateTypeMap->getType($templateTypeName);
            if ($templateType === null) {
                return $type;
            }
            $bound = TemplateTypeHelper::resolveToBounds($templateType);
            if ($bound instanceof \PHPStan\Type\MixedType && $bound->isExplicitMixed()) {
                return new \PHPStan\Type\MixedType(\false);
            }
            return $bound;
        }
        return $type;
    }
    public function getConstantStrings() : array
    {
        return [];
    }
    public function isIterable() : TrinaryLogic
    {
        return $this->isInstanceOf(Traversable::class);
    }
    public function isIterableAtLeastOnce() : TrinaryLogic
    {
        return $this->isInstanceOf(Traversable::class)->and(TrinaryLogic::createMaybe());
    }
    public function getArraySize() : \PHPStan\Type\Type
    {
        if ($this->isInstanceOf(Countable::class)->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return \PHPStan\Type\IntegerRangeType::fromInterval(0, null);
    }
    public function getIterableKeyType() : \PHPStan\Type\Type
    {
        $isTraversable = \false;
        if ($this->isInstanceOf(IteratorAggregate::class)->yes()) {
            $keyType = \PHPStan\Type\RecursionGuard::run($this, function () : \PHPStan\Type\Type {
                return ParametersAcceptorSelector::selectSingle($this->getMethod('getIterator', new OutOfClassScope())->getVariants())->getReturnType()->getIterableKeyType();
            });
            $isTraversable = \true;
            if (!$keyType instanceof \PHPStan\Type\MixedType || $keyType->isExplicitMixed()) {
                return $keyType;
            }
        }
        $extraOffsetAccessible = $this->isExtraOffsetAccessibleClass()->yes();
        if (!$extraOffsetAccessible && $this->isInstanceOf(Traversable::class)->yes()) {
            $isTraversable = \true;
            $tKey = $this->getTemplateType(Traversable::class, 'TKey');
            if (!$tKey instanceof \PHPStan\Type\ErrorType) {
                if (!$tKey instanceof \PHPStan\Type\MixedType || $tKey->isExplicitMixed()) {
                    return $tKey;
                }
            }
        }
        if ($this->isInstanceOf(Iterator::class)->yes()) {
            return \PHPStan\Type\RecursionGuard::run($this, function () : \PHPStan\Type\Type {
                return ParametersAcceptorSelector::selectSingle($this->getMethod('key', new OutOfClassScope())->getVariants())->getReturnType();
            });
        }
        if ($extraOffsetAccessible) {
            return new \PHPStan\Type\MixedType(\true);
        }
        if ($isTraversable) {
            return new \PHPStan\Type\MixedType();
        }
        return new \PHPStan\Type\ErrorType();
    }
    public function getFirstIterableKeyType() : \PHPStan\Type\Type
    {
        return $this->getIterableKeyType();
    }
    public function getLastIterableKeyType() : \PHPStan\Type\Type
    {
        return $this->getIterableKeyType();
    }
    public function getIterableValueType() : \PHPStan\Type\Type
    {
        $isTraversable = \false;
        if ($this->isInstanceOf(IteratorAggregate::class)->yes()) {
            $valueType = \PHPStan\Type\RecursionGuard::run($this, function () : \PHPStan\Type\Type {
                return ParametersAcceptorSelector::selectSingle($this->getMethod('getIterator', new OutOfClassScope())->getVariants())->getReturnType()->getIterableValueType();
            });
            $isTraversable = \true;
            if (!$valueType instanceof \PHPStan\Type\MixedType || $valueType->isExplicitMixed()) {
                return $valueType;
            }
        }
        $extraOffsetAccessible = $this->isExtraOffsetAccessibleClass()->yes();
        if (!$extraOffsetAccessible && $this->isInstanceOf(Traversable::class)->yes()) {
            $isTraversable = \true;
            $tValue = $this->getTemplateType(Traversable::class, 'TValue');
            if (!$tValue instanceof \PHPStan\Type\ErrorType) {
                if (!$tValue instanceof \PHPStan\Type\MixedType || $tValue->isExplicitMixed()) {
                    return $tValue;
                }
            }
        }
        if ($this->isInstanceOf(Iterator::class)->yes()) {
            return \PHPStan\Type\RecursionGuard::run($this, function () : \PHPStan\Type\Type {
                return ParametersAcceptorSelector::selectSingle($this->getMethod('current', new OutOfClassScope())->getVariants())->getReturnType();
            });
        }
        if ($extraOffsetAccessible) {
            return new \PHPStan\Type\MixedType(\true);
        }
        if ($isTraversable) {
            return new \PHPStan\Type\MixedType();
        }
        return new \PHPStan\Type\ErrorType();
    }
    public function getFirstIterableValueType() : \PHPStan\Type\Type
    {
        return $this->getIterableValueType();
    }
    public function getLastIterableValueType() : \PHPStan\Type\Type
    {
        return $this->getIterableValueType();
    }
    public function isNull() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isConstantValue() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isConstantScalarValue() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function getConstantScalarTypes() : array
    {
        return [];
    }
    public function getConstantScalarValues() : array
    {
        return [];
    }
    public function isTrue() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isFalse() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isBoolean() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isFloat() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isInteger() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isString() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isNumericString() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isNonEmptyString() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isNonFalsyString() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isLiteralString() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isClassStringType() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function getClassStringObjectType() : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function getObjectTypeOrClassStringObjectType() : \PHPStan\Type\Type
    {
        return $this;
    }
    public function isVoid() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isScalar() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function looseCompare(\PHPStan\Type\Type $type, PhpVersion $phpVersion) : \PHPStan\Type\BooleanType
    {
        if ($type->isTrue()->yes()) {
            return new ConstantBooleanType(\true);
        }
        return $type->isFalse()->yes() ? new ConstantBooleanType(\false) : new \PHPStan\Type\BooleanType();
    }
    private function isExtraOffsetAccessibleClass() : TrinaryLogic
    {
        $classReflection = $this->getClassReflection();
        if ($classReflection === null) {
            return TrinaryLogic::createMaybe();
        }
        foreach (self::EXTRA_OFFSET_CLASSES as $extraOffsetClass) {
            if ($classReflection->getName() === $extraOffsetClass) {
                return TrinaryLogic::createYes();
            }
            if ($classReflection->isSubclassOf($extraOffsetClass)) {
                return TrinaryLogic::createYes();
            }
        }
        if ($classReflection->isInterface()) {
            return TrinaryLogic::createMaybe();
        }
        if ($classReflection->isFinal()) {
            return TrinaryLogic::createNo();
        }
        return TrinaryLogic::createMaybe();
    }
    public function isOffsetAccessible() : TrinaryLogic
    {
        return $this->isInstanceOf(ArrayAccess::class)->or($this->isExtraOffsetAccessibleClass());
    }
    public function hasOffsetValueType(\PHPStan\Type\Type $offsetType) : TrinaryLogic
    {
        if ($this->isInstanceOf(ArrayAccess::class)->yes()) {
            $acceptedOffsetType = \PHPStan\Type\RecursionGuard::run($this, function () : \PHPStan\Type\Type {
                $parameters = ParametersAcceptorSelector::selectSingle($this->getMethod('offsetSet', new OutOfClassScope())->getVariants())->getParameters();
                if (count($parameters) < 2) {
                    throw new ShouldNotHappenException(sprintf('Method %s::%s() has less than 2 parameters.', $this->className, 'offsetSet'));
                }
                $offsetParameter = $parameters[0];
                return $offsetParameter->getType();
            });
            if ($acceptedOffsetType->isSuperTypeOf($offsetType)->no()) {
                return TrinaryLogic::createNo();
            }
            return TrinaryLogic::createMaybe();
        }
        return $this->isExtraOffsetAccessibleClass()->and(TrinaryLogic::createMaybe());
    }
    public function getOffsetValueType(\PHPStan\Type\Type $offsetType) : \PHPStan\Type\Type
    {
        if (!$this->isExtraOffsetAccessibleClass()->no()) {
            return new \PHPStan\Type\MixedType();
        }
        if ($this->isInstanceOf(ArrayAccess::class)->yes()) {
            return \PHPStan\Type\RecursionGuard::run($this, function () : \PHPStan\Type\Type {
                return ParametersAcceptorSelector::selectSingle($this->getMethod('offsetGet', new OutOfClassScope())->getVariants())->getReturnType();
            });
        }
        return new \PHPStan\Type\ErrorType();
    }
    public function setOffsetValueType(?\PHPStan\Type\Type $offsetType, \PHPStan\Type\Type $valueType, bool $unionValues = \true) : \PHPStan\Type\Type
    {
        if ($this->isOffsetAccessible()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        if ($this->isInstanceOf(ArrayAccess::class)->yes()) {
            $acceptedValueType = new \PHPStan\Type\NeverType();
            $acceptedOffsetType = \PHPStan\Type\RecursionGuard::run($this, function () use(&$acceptedValueType) : \PHPStan\Type\Type {
                $parameters = ParametersAcceptorSelector::selectSingle($this->getMethod('offsetSet', new OutOfClassScope())->getVariants())->getParameters();
                if (count($parameters) < 2) {
                    throw new ShouldNotHappenException(sprintf('Method %s::%s() has less than 2 parameters.', $this->className, 'offsetSet'));
                }
                $offsetParameter = $parameters[0];
                $acceptedValueType = $parameters[1]->getType();
                return $offsetParameter->getType();
            });
            if ($offsetType === null) {
                $offsetType = new \PHPStan\Type\NullType();
            }
            if (!$offsetType instanceof \PHPStan\Type\MixedType && !$acceptedOffsetType->isSuperTypeOf($offsetType)->yes() || !$valueType instanceof \PHPStan\Type\MixedType && !$acceptedValueType->isSuperTypeOf($valueType)->yes()) {
                return new \PHPStan\Type\ErrorType();
            }
        }
        // in the future we may return intersection of $this and OffsetAccessibleType()
        return $this;
    }
    public function unsetOffset(\PHPStan\Type\Type $offsetType) : \PHPStan\Type\Type
    {
        if ($this->isOffsetAccessible()->no()) {
            return new \PHPStan\Type\ErrorType();
        }
        return $this;
    }
    public function getEnumCases() : array
    {
        $classReflection = $this->getClassReflection();
        if ($classReflection === null) {
            return [];
        }
        if (!$classReflection->isEnum()) {
            return [];
        }
        $subtracted = [];
        if ($this->subtractedType !== null) {
            foreach ($this->subtractedType->getEnumCases() as $enumCase) {
                $subtracted[$enumCase->getEnumCaseName()] = \true;
            }
        }
        $cases = [];
        foreach ($classReflection->getEnumCases() as $enumCase) {
            if (array_key_exists($enumCase->getName(), $subtracted)) {
                continue;
            }
            $cases[] = new EnumCaseObjectType($classReflection->getName(), $enumCase->getName(), $classReflection);
        }
        return $cases;
    }
    public function isCallable() : TrinaryLogic
    {
        $parametersAcceptors = $this->findCallableParametersAcceptors();
        if ($parametersAcceptors === null) {
            return TrinaryLogic::createNo();
        }
        if (count($parametersAcceptors) === 1 && $parametersAcceptors[0] instanceof TrivialParametersAcceptor) {
            return TrinaryLogic::createMaybe();
        }
        return TrinaryLogic::createYes();
    }
    /**
     * @return ParametersAcceptor[]
     */
    public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope) : array
    {
        if ($this->className === Closure::class) {
            return [new TrivialParametersAcceptor()];
        }
        $parametersAcceptors = $this->findCallableParametersAcceptors();
        if ($parametersAcceptors === null) {
            throw new ShouldNotHappenException();
        }
        return $parametersAcceptors;
    }
    /**
     * @return ParametersAcceptor[]|null
     */
    private function findCallableParametersAcceptors() : ?array
    {
        $classReflection = $this->getClassReflection();
        if ($classReflection === null) {
            return [new TrivialParametersAcceptor()];
        }
        if ($classReflection->hasNativeMethod('__invoke')) {
            return $this->getMethod('__invoke', new OutOfClassScope())->getVariants();
        }
        if (!$classReflection->getNativeReflection()->isFinal()) {
            return [new TrivialParametersAcceptor()];
        }
        return null;
    }
    public function isCloneable() : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    /**
     * @param mixed[] $properties
     */
    public static function __set_state(array $properties) : \PHPStan\Type\Type
    {
        return new self($properties['className'], $properties['subtractedType'] ?? null);
    }
    public function isInstanceOf(string $className) : TrinaryLogic
    {
        $classReflection = $this->getClassReflection();
        if ($classReflection === null) {
            return TrinaryLogic::createMaybe();
        }
        if ($classReflection->getName() === $className || $classReflection->isSubclassOf($className)) {
            return TrinaryLogic::createYes();
        }
        $reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
        if ($reflectionProvider->hasClass($className)) {
            $thatClassReflection = $reflectionProvider->getClass($className);
            if ($thatClassReflection->isFinal()) {
                return TrinaryLogic::createNo();
            }
        }
        if ($classReflection->isInterface()) {
            return TrinaryLogic::createMaybe();
        }
        return TrinaryLogic::createNo();
    }
    public function subtract(\PHPStan\Type\Type $type) : \PHPStan\Type\Type
    {
        if ($this->subtractedType !== null) {
            $type = \PHPStan\Type\TypeCombinator::union($this->subtractedType, $type);
        }
        return $this->changeSubtractedType($type);
    }
    public function getTypeWithoutSubtractedType() : \PHPStan\Type\Type
    {
        return $this->changeSubtractedType(null);
    }
    public function changeSubtractedType(?\PHPStan\Type\Type $subtractedType) : \PHPStan\Type\Type
    {
        if ($subtractedType !== null) {
            $classReflection = $this->getClassReflection();
            $allowedSubTypesList = $classReflection !== null ? $classReflection->getAllowedSubTypes() : null;
            if ($allowedSubTypesList !== null) {
                $allowedSubTypes = [];
                foreach ($allowedSubTypesList as $allowedSubType) {
                    $allowedSubTypes[$allowedSubType->describe(\PHPStan\Type\VerbosityLevel::precise())] = $allowedSubType;
                }
                $originalAllowedSubTypes = $allowedSubTypes;
                $subtractedSubTypes = [];
                $subtractedTypesList = \PHPStan\Type\TypeUtils::flattenTypes($subtractedType);
                $subtractedTypes = [];
                foreach ($subtractedTypesList as $type) {
                    $subtractedTypes[$type->describe(\PHPStan\Type\VerbosityLevel::precise())] = $type;
                }
                foreach ($subtractedTypes as $subType) {
                    foreach ($allowedSubTypes as $description => $allowedSubType) {
                        if ($subType->equals($allowedSubType)) {
                            $subtractedSubTypes[$description] = $subType;
                            unset($allowedSubTypes[$description]);
                            continue 2;
                        }
                    }
                    return new self($this->className, $subtractedType);
                }
                if (count($allowedSubTypes) === 1) {
                    return array_values($allowedSubTypes)[0];
                }
                $subtractedSubTypes = array_values($subtractedSubTypes);
                $subtractedSubTypesCount = count($subtractedSubTypes);
                if ($subtractedSubTypesCount === count($originalAllowedSubTypes)) {
                    return new \PHPStan\Type\NeverType();
                }
                if ($subtractedSubTypesCount === 0) {
                    return new self($this->className);
                }
                if ($subtractedSubTypesCount === 1) {
                    return new self($this->className, $subtractedSubTypes[0]);
                }
                return new self($this->className, new \PHPStan\Type\UnionType($subtractedSubTypes));
            }
        }
        if ($this->subtractedType === null && $subtractedType === null) {
            return $this;
        }
        return new self($this->className, $subtractedType);
    }
    public function getSubtractedType() : ?\PHPStan\Type\Type
    {
        return $this->subtractedType;
    }
    public function traverse(callable $cb) : \PHPStan\Type\Type
    {
        $subtractedType = $this->subtractedType !== null ? $cb($this->subtractedType) : null;
        if ($subtractedType !== $this->subtractedType) {
            return new self($this->className, $subtractedType);
        }
        return $this;
    }
    public function traverseSimultaneously(\PHPStan\Type\Type $right, callable $cb) : \PHPStan\Type\Type
    {
        if ($this->subtractedType === null) {
            return $this;
        }
        return new self($this->className);
    }
    public function getNakedClassReflection() : ?ClassReflection
    {
        if ($this->classReflection !== null) {
            return $this->classReflection;
        }
        $reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
        if (!$reflectionProvider->hasClass($this->className)) {
            return null;
        }
        return $reflectionProvider->getClass($this->className);
    }
    public function getClassReflection() : ?ClassReflection
    {
        if ($this->classReflection !== null) {
            return $this->classReflection;
        }
        $reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
        if (!$reflectionProvider->hasClass($this->className)) {
            return null;
        }
        $classReflection = $reflectionProvider->getClass($this->className);
        if ($classReflection->isGeneric()) {
            return $classReflection->withTypes(array_values($classReflection->getTemplateTypeMap()->map(static function () : \PHPStan\Type\Type {
                return new \PHPStan\Type\ErrorType();
            })->getTypes()));
        }
        return $classReflection;
    }
    /**
     * @return self|null
     */
    public function getAncestorWithClassName(string $className) : ?\PHPStan\Type\TypeWithClassName
    {
        if ($this->className === $className) {
            return $this;
        }
        if ($this->classReflection !== null && $className === $this->classReflection->getName()) {
            return $this;
        }
        if (array_key_exists($className, $this->currentAncestors)) {
            return $this->currentAncestors[$className];
        }
        $description = $this->describeCache();
        if (array_key_exists($description, self::$ancestors) && array_key_exists($className, self::$ancestors[$description])) {
            return self::$ancestors[$description][$className];
        }
        $reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
        if (!$reflectionProvider->hasClass($className)) {
            return self::$ancestors[$description][$className] = $this->currentAncestors[$className] = null;
        }
        $theirReflection = $reflectionProvider->getClass($className);
        $thisReflection = $this->getClassReflection();
        if ($thisReflection === null) {
            return self::$ancestors[$description][$className] = $this->currentAncestors[$className] = null;
        }
        if ($theirReflection->getName() === $thisReflection->getName()) {
            return self::$ancestors[$description][$className] = $this->currentAncestors[$className] = $this;
        }
        foreach ($this->getInterfaces() as $interface) {
            $ancestor = $interface->getAncestorWithClassName($className);
            if ($ancestor !== null) {
                return self::$ancestors[$description][$className] = $this->currentAncestors[$className] = $ancestor;
            }
        }
        $parent = $this->getParent();
        if ($parent !== null) {
            $ancestor = $parent->getAncestorWithClassName($className);
            if ($ancestor !== null) {
                return self::$ancestors[$description][$className] = $this->currentAncestors[$className] = $ancestor;
            }
        }
        return self::$ancestors[$description][$className] = $this->currentAncestors[$className] = null;
    }
    private function getParent() : ?\PHPStan\Type\ObjectType
    {
        if ($this->cachedParent !== null) {
            return $this->cachedParent;
        }
        $thisReflection = $this->getClassReflection();
        if ($thisReflection === null) {
            return null;
        }
        $parentReflection = $thisReflection->getParentClass();
        if ($parentReflection === null) {
            return null;
        }
        return $this->cachedParent = self::createFromReflection($parentReflection);
    }
    /** @return ObjectType[] */
    private function getInterfaces() : array
    {
        if ($this->cachedInterfaces !== null) {
            return $this->cachedInterfaces;
        }
        $thisReflection = $this->getClassReflection();
        if ($thisReflection === null) {
            return $this->cachedInterfaces = [];
        }
        return $this->cachedInterfaces = array_map(static function (ClassReflection $interfaceReflection) : self {
            return self::createFromReflection($interfaceReflection);
        }, $thisReflection->getInterfaces());
    }
    public function tryRemove(\PHPStan\Type\Type $typeToRemove) : ?\PHPStan\Type\Type
    {
        if ($this->getClassName() === DateTimeInterface::class) {
            if ($typeToRemove instanceof \PHPStan\Type\ObjectType && $typeToRemove->getClassName() === DateTimeImmutable::class) {
                return new \PHPStan\Type\ObjectType(DateTime::class);
            }
            if ($typeToRemove instanceof \PHPStan\Type\ObjectType && $typeToRemove->getClassName() === DateTime::class) {
                return new \PHPStan\Type\ObjectType(DateTimeImmutable::class);
            }
        }
        if ($this->getClassName() === Throwable::class) {
            if ($typeToRemove instanceof \PHPStan\Type\ObjectType && $typeToRemove->getClassName() === Error::class) {
                return new \PHPStan\Type\ObjectType(Exception::class);
                // phpcs:ignore SlevomatCodingStandard.Exceptions.ReferenceThrowableOnly.ReferencedGeneralException
            }
            if ($typeToRemove instanceof \PHPStan\Type\ObjectType && $typeToRemove->getClassName() === Exception::class) {
                // phpcs:ignore SlevomatCodingStandard.Exceptions.ReferenceThrowableOnly.ReferencedGeneralException
                return new \PHPStan\Type\ObjectType(Error::class);
            }
        }
        if ($this->isSuperTypeOf($typeToRemove)->yes()) {
            return $this->subtract($typeToRemove);
        }
        return null;
    }
    public function getFiniteTypes() : array
    {
        return $this->getEnumCases();
    }
    public function exponentiate(\PHPStan\Type\Type $exponent) : \PHPStan\Type\Type
    {
        $object = new \PHPStan\Type\ObjectWithoutClassType();
        if (!$exponent instanceof \PHPStan\Type\NeverType && !$object->isSuperTypeOf($this)->no() && !$object->isSuperTypeOf($exponent)->no()) {
            return \PHPStan\Type\TypeCombinator::union($this, $exponent);
        }
        return new \PHPStan\Type\ErrorType();
    }
    public function toPhpDocNode() : TypeNode
    {
        return new IdentifierTypeNode($this->getClassName());
    }
}