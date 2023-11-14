<?php

declare (strict_types=1);
namespace PHPStan\Reflection\SignatureMap;

use PHPStan\BetterReflection\Identifier\Exception\InvalidIdentifierName;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDoc\StubPhpDocProvider;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\FunctionVariantWithPhpDocs;
use PHPStan\Reflection\Native\NativeFunctionReflection;
use PHPStan\Reflection\Native\NativeParameterWithPhpDocsReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
use function array_key_exists;
use function array_map;
use function strtolower;
class NativeFunctionReflectionProvider
{
    /**
     * @var \PHPStan\Reflection\SignatureMap\SignatureMapProvider
     */
    private $signatureMapProvider;
    /**
     * @var \PHPStan\BetterReflection\Reflector\Reflector
     */
    private $reflector;
    /**
     * @var \PHPStan\Type\FileTypeMapper
     */
    private $fileTypeMapper;
    /**
     * @var \PHPStan\PhpDoc\StubPhpDocProvider
     */
    private $stubPhpDocProvider;
    /** @var NativeFunctionReflection[] */
    private $functionMap = [];
    public function __construct(\PHPStan\Reflection\SignatureMap\SignatureMapProvider $signatureMapProvider, Reflector $reflector, FileTypeMapper $fileTypeMapper, StubPhpDocProvider $stubPhpDocProvider)
    {
        $this->signatureMapProvider = $signatureMapProvider;
        $this->reflector = $reflector;
        $this->fileTypeMapper = $fileTypeMapper;
        $this->stubPhpDocProvider = $stubPhpDocProvider;
    }
    public function findFunctionReflection(string $functionName) : ?NativeFunctionReflection
    {
        $lowerCasedFunctionName = strtolower($functionName);
        $realFunctionName = $lowerCasedFunctionName;
        if (isset($this->functionMap[$lowerCasedFunctionName])) {
            return $this->functionMap[$lowerCasedFunctionName];
        }
        if (!$this->signatureMapProvider->hasFunctionSignature($lowerCasedFunctionName)) {
            return null;
        }
        $throwType = null;
        $reflectionFunctionAdapter = null;
        $isDeprecated = \false;
        $phpDocReturnType = null;
        $asserts = Assertions::createEmpty();
        $docComment = null;
        $returnsByReference = TrinaryLogic::createMaybe();
        try {
            $reflectionFunction = $this->reflector->reflectFunction($functionName);
            $reflectionFunctionAdapter = new ReflectionFunction($reflectionFunction);
            $returnsByReference = TrinaryLogic::createFromBoolean($reflectionFunctionAdapter->returnsReference());
            $realFunctionName = $reflectionFunction->getName();
            if ($reflectionFunction->getFileName() !== null) {
                $fileName = $reflectionFunction->getFileName();
                $docComment = $reflectionFunction->getDocComment();
                if ($docComment !== null) {
                    $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc($fileName, null, null, $reflectionFunction->getName(), $docComment);
                    $throwsTag = $resolvedPhpDoc->getThrowsTag();
                    if ($throwsTag !== null) {
                        $throwType = $throwsTag->getType();
                    }
                    $isDeprecated = $reflectionFunction->isDeprecated();
                }
            }
        } catch (IdentifierNotFound|InvalidIdentifierName $exception) {
            // pass
        }
        $functionSignatures = $this->signatureMapProvider->getFunctionSignatures($lowerCasedFunctionName, null, $reflectionFunctionAdapter);
        $phpDoc = $this->stubPhpDocProvider->findFunctionPhpDoc($lowerCasedFunctionName, array_map(static function (\PHPStan\Reflection\SignatureMap\ParameterSignature $parameter) : string {
            return $parameter->getName();
        }, $functionSignatures[0]->getParameters()));
        if ($phpDoc !== null) {
            if ($phpDoc->hasPhpDocString()) {
                $docComment = $phpDoc->getPhpDocString();
            }
            if ($phpDoc->getThrowsTag() !== null) {
                $throwType = $phpDoc->getThrowsTag()->getType();
            }
            $asserts = Assertions::createFromResolvedPhpDocBlock($phpDoc);
            $phpDocReturnType = $this->getReturnTypeFromPhpDoc($phpDoc);
        }
        $variants = [];
        $functionSignatures = $this->signatureMapProvider->getFunctionSignatures($lowerCasedFunctionName, null, $reflectionFunctionAdapter);
        foreach ($functionSignatures as $functionSignature) {
            $variants[] = new FunctionVariantWithPhpDocs(TemplateTypeMap::createEmpty(), null, array_map(static function (\PHPStan\Reflection\SignatureMap\ParameterSignature $parameterSignature) use($phpDoc) : NativeParameterWithPhpDocsReflection {
                $type = $parameterSignature->getType();
                $phpDocType = null;
                if ($phpDoc !== null) {
                    $phpDocParam = $phpDoc->getParamTags()[$parameterSignature->getName()] ?? null;
                    if ($phpDocParam !== null) {
                        $phpDocType = $phpDocParam->getType();
                    }
                }
                return new NativeParameterWithPhpDocsReflection($parameterSignature->getName(), $parameterSignature->isOptional(), TypehintHelper::decideType($type, $phpDocType), $phpDocType ?? new MixedType(), $type, $parameterSignature->passedByReference(), $parameterSignature->isVariadic(), $parameterSignature->getDefaultValue(), $phpDoc !== null ? \PHPStan\Reflection\SignatureMap\NativeFunctionReflectionProvider::getParamOutTypeFromPhpDoc($parameterSignature->getName(), $phpDoc) : null);
            }, $functionSignature->getParameters()), $functionSignature->isVariadic(), TypehintHelper::decideType($functionSignature->getReturnType(), $phpDocReturnType), $phpDocReturnType ?? new MixedType(), $functionSignature->getReturnType());
        }
        if ($this->signatureMapProvider->hasFunctionMetadata($lowerCasedFunctionName)) {
            $hasSideEffects = TrinaryLogic::createFromBoolean($this->signatureMapProvider->getFunctionMetadata($lowerCasedFunctionName)['hasSideEffects']);
        } else {
            $hasSideEffects = TrinaryLogic::createMaybe();
        }
        $functionReflection = new NativeFunctionReflection($realFunctionName, $variants, $throwType, $hasSideEffects, $isDeprecated, $asserts, $docComment, $returnsByReference);
        $this->functionMap[$lowerCasedFunctionName] = $functionReflection;
        return $functionReflection;
    }
    private function getReturnTypeFromPhpDoc(ResolvedPhpDocBlock $phpDoc) : ?Type
    {
        $returnTag = $phpDoc->getReturnTag();
        if ($returnTag === null) {
            return null;
        }
        return $returnTag->getType();
    }
    private static function getParamOutTypeFromPhpDoc(string $paramName, ResolvedPhpDocBlock $stubPhpDoc) : ?Type
    {
        $paramOutTags = $stubPhpDoc->getParamOutTags();
        if (array_key_exists($paramName, $paramOutTags)) {
            return $paramOutTags[$paramName]->getType();
        }
        return null;
    }
}
