<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection;

use _PHPStan_c6b09fbdf\Nette\DI\Config\Adapter;
use _PHPStan_c6b09fbdf\Nette\DI\Config\Helpers;
use _PHPStan_c6b09fbdf\Nette\DI\Definitions\Reference;
use _PHPStan_c6b09fbdf\Nette\DI\Definitions\Statement;
use _PHPStan_c6b09fbdf\Nette\DI\InvalidConfigurationException;
use _PHPStan_c6b09fbdf\Nette\Neon\Entity;
use _PHPStan_c6b09fbdf\Nette\Neon\Exception;
use _PHPStan_c6b09fbdf\Nette\Neon\Neon;
use PHPStan\File\FileHelper;
use PHPStan\File\FileReader;
use function array_values;
use function array_walk_recursive;
use function dirname;
use function implode;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function ltrim;
use function sprintf;
use function strpos;
use function substr;
class NeonAdapter implements Adapter
{
    public const CACHE_KEY = 'v23-pro-tmp-dir';
    private const PREVENT_MERGING_SUFFIX = '!';
    /** @var FileHelper[] */
    private $fileHelpers = [];
    /**
     * @return mixed[]
     */
    public function load(string $file) : array
    {
        $contents = FileReader::read($file);
        try {
            return $this->process((array) Neon::decode($contents), '', $file);
        } catch (Exception $e) {
            throw new Exception(sprintf('Error while loading %s: %s', $file, $e->getMessage()));
        }
    }
    /**
     * @param mixed[] $arr
     * @return mixed[]
     */
    public function process(array $arr, string $fileKey, string $file) : array
    {
        $res = [];
        foreach ($arr as $key => $val) {
            if (is_string($key) && substr($key, -1) === self::PREVENT_MERGING_SUFFIX) {
                if (!is_array($val) && $val !== null) {
                    throw new InvalidConfigurationException(sprintf('Replacing operator is available only for arrays, item \'%s\' is not array.', $key));
                }
                $key = substr($key, 0, -1);
                $val[Helpers::PREVENT_MERGING] = \true;
            }
            if (is_array($val)) {
                if (!is_int($key)) {
                    $fileKeyToPass = $fileKey . '[' . $key . ']';
                } else {
                    $fileKeyToPass = $fileKey . '[]';
                }
                $val = $this->process($val, $fileKeyToPass, $file);
            } elseif ($val instanceof Entity) {
                if (!is_int($key)) {
                    $fileKeyToPass = $fileKey . '(' . $key . ')';
                } else {
                    $fileKeyToPass = $fileKey . '()';
                }
                if ($val->value === Neon::CHAIN) {
                    $tmp = null;
                    foreach ($this->process($val->attributes, $fileKeyToPass, $file) as $st) {
                        $tmp = new Statement($tmp === null ? $st->getEntity() : [$tmp, ltrim(implode('::', (array) $st->getEntity()), ':')], $st->arguments);
                    }
                    $val = $tmp;
                } else {
                    $tmp = $this->process([$val->value], $fileKeyToPass, $file);
                    $val = new Statement($tmp[0], $this->process($val->attributes, $fileKeyToPass, $file));
                }
            }
            $keyToResolve = $fileKey;
            if (is_int($key)) {
                $keyToResolve .= '[]';
            } else {
                $keyToResolve .= '[' . $key . ']';
            }
            if (in_array($keyToResolve, ['[parameters][paths][]', '[parameters][excludes_analyse][]', '[parameters][excludePaths][]', '[parameters][excludePaths][analyse][]', '[parameters][excludePaths][analyseAndScan][]', '[parameters][ignoreErrors][][paths][]', '[parameters][ignoreErrors][][path]', '[parameters][bootstrapFiles][]', '[parameters][scanFiles][]', '[parameters][scanDirectories][]', '[parameters][tmpDir]', '[parameters][pro][tmpDir]', '[parameters][memoryLimitFile]', '[parameters][benchmarkFile]', '[parameters][stubFiles][]', '[parameters][symfony][console_application_loader]', '[parameters][symfony][consoleApplicationLoader]', '[parameters][symfony][container_xml_path]', '[parameters][symfony][containerXmlPath]', '[parameters][doctrine][objectManagerLoader]'], \true) && is_string($val) && strpos($val, '%') === \false && strpos($val, '*') !== 0) {
                $fileHelper = $this->createFileHelperByFile($file);
                $val = $fileHelper->normalizePath($fileHelper->absolutizePath($val));
            }
            if ($keyToResolve === '[parameters][excludePaths]' && $val !== null && array_values($val) === $val) {
                $val = ['analyseAndScan' => $val, 'analyse' => []];
            }
            $res[$key] = $val;
        }
        return $res;
    }
    /**
     * @param mixed[] $data
     */
    public function dump(array $data) : string
    {
        array_walk_recursive($data, static function (&$val) : void {
            if (!$val instanceof Statement) {
                return;
            }
            $val = self::statementToEntity($val);
        });
        return "# generated by Nette\n\n" . Neon::encode($data, Neon::BLOCK);
    }
    private static function statementToEntity(Statement $val) : Entity
    {
        array_walk_recursive($val->arguments, static function (&$val) : void {
            if ($val instanceof Statement) {
                $val = self::statementToEntity($val);
            } elseif ($val instanceof Reference) {
                $val = '@' . $val->getValue();
            }
        });
        $entity = $val->getEntity();
        if ($entity instanceof Reference) {
            $entity = '@' . $entity->getValue();
        } elseif (is_array($entity)) {
            if ($entity[0] instanceof Statement) {
                return new Entity(Neon::CHAIN, [self::statementToEntity($entity[0]), new Entity('::' . $entity[1], $val->arguments)]);
            } elseif ($entity[0] instanceof Reference) {
                $entity = '@' . $entity[0]->getValue() . '::' . $entity[1];
            } elseif (is_string($entity[0])) {
                $entity = $entity[0] . '::' . $entity[1];
            }
        }
        return new Entity($entity, $val->arguments);
    }
    private function createFileHelperByFile(string $file) : FileHelper
    {
        $dir = dirname($file);
        if (!isset($this->fileHelpers[$dir])) {
            $this->fileHelpers[$dir] = new FileHelper($dir);
        }
        return $this->fileHelpers[$dir];
    }
}