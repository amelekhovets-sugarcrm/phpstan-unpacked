<?php

declare (strict_types=1);
namespace PHPStan\Parser;

use PHPStan\File\FileHelper;
use function array_fill_keys;
use function strpos;
class PathRoutingParser implements \PHPStan\Parser\Parser
{
    /**
     * @var \PHPStan\File\FileHelper
     */
    private $fileHelper;
    /**
     * @var \PHPStan\Parser\Parser
     */
    private $currentPhpVersionRichParser;
    /**
     * @var \PHPStan\Parser\Parser
     */
    private $currentPhpVersionSimpleParser;
    /**
     * @var \PHPStan\Parser\Parser
     */
    private $php8Parser;
    /** @var bool[] filePath(string) => bool(true) */
    private $analysedFiles = [];
    public function __construct(FileHelper $fileHelper, \PHPStan\Parser\Parser $currentPhpVersionRichParser, \PHPStan\Parser\Parser $currentPhpVersionSimpleParser, \PHPStan\Parser\Parser $php8Parser)
    {
        $this->fileHelper = $fileHelper;
        $this->currentPhpVersionRichParser = $currentPhpVersionRichParser;
        $this->currentPhpVersionSimpleParser = $currentPhpVersionSimpleParser;
        $this->php8Parser = $php8Parser;
    }
    /**
     * @param string[] $files
     */
    public function setAnalysedFiles(array $files) : void
    {
        $this->analysedFiles = array_fill_keys($files, \true);
    }
    public function parseFile(string $file) : array
    {
        $normalizedPath = $this->fileHelper->normalizePath($file, '/');
        if (strpos($normalizedPath, 'vendor/jetbrains/phpstorm-stubs') !== \false) {
            return $this->php8Parser->parseFile($file);
        }
        if (strpos($normalizedPath, 'vendor/phpstan/php-8-stubs/stubs') !== \false) {
            return $this->php8Parser->parseFile($file);
        }
        $file = $this->fileHelper->normalizePath($file);
        if (!isset($this->analysedFiles[$file])) {
            return $this->currentPhpVersionSimpleParser->parseFile($file);
        }
        return $this->currentPhpVersionRichParser->parseFile($file);
    }
    public function parseString(string $sourceCode) : array
    {
        return $this->currentPhpVersionSimpleParser->parseString($sourceCode);
    }
}
