<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use _PHPStan_c6b09fbdf\Nette\Utils\Json;
use _PHPStan_c6b09fbdf\Nette\Utils\JsonException;
use PHPStan\File\FileHelper;
use function array_key_exists;
use function array_values;
use function is_array;
use function is_file;
use function sprintf;
class IgnoredErrorHelper
{
    /**
     * @var \PHPStan\File\FileHelper
     */
    private $fileHelper;
    /**
     * @var (string | mixed[])[]
     */
    private $ignoreErrors;
    /**
     * @var bool
     */
    private $reportUnmatchedIgnoredErrors;
    /**
     * @param (string|mixed[])[] $ignoreErrors
     */
    public function __construct(FileHelper $fileHelper, array $ignoreErrors, bool $reportUnmatchedIgnoredErrors)
    {
        $this->fileHelper = $fileHelper;
        $this->ignoreErrors = $ignoreErrors;
        $this->reportUnmatchedIgnoredErrors = $reportUnmatchedIgnoredErrors;
    }
    public function initialize() : \PHPStan\Analyser\IgnoredErrorHelperResult
    {
        $otherIgnoreErrors = [];
        $ignoreErrorsByFile = [];
        $errors = [];
        $expandedIgnoreErrors = [];
        foreach ($this->ignoreErrors as $ignoreError) {
            if (is_array($ignoreError)) {
                if (!isset($ignoreError['message']) && !isset($ignoreError['messages'])) {
                    $errors[] = sprintf('Ignored error %s is missing a message.', Json::encode($ignoreError));
                    continue;
                }
                if (isset($ignoreError['messages'])) {
                    foreach ($ignoreError['messages'] as $message) {
                        $expandedIgnoreError = $ignoreError;
                        unset($expandedIgnoreError['messages']);
                        $expandedIgnoreError['message'] = $message;
                        $expandedIgnoreErrors[] = $expandedIgnoreError;
                    }
                } else {
                    $expandedIgnoreErrors[] = $ignoreError;
                }
            } else {
                $expandedIgnoreErrors[] = $ignoreError;
            }
        }
        $uniquedExpandedIgnoreErrors = [];
        foreach ($expandedIgnoreErrors as $ignoreError) {
            if (!isset($ignoreError['message'])) {
                $uniquedExpandedIgnoreErrors[] = $ignoreError;
                continue;
            }
            if (!isset($ignoreError['path'])) {
                $uniquedExpandedIgnoreErrors[] = $ignoreError;
                continue;
            }
            $key = sprintf("%s\n%s", $ignoreError['message'], $ignoreError['path']);
            if (!array_key_exists($key, $uniquedExpandedIgnoreErrors)) {
                $uniquedExpandedIgnoreErrors[$key] = $ignoreError;
                continue;
            }
            $uniquedExpandedIgnoreErrors[$key] = ['message' => $ignoreError['message'], 'path' => $ignoreError['path'], 'count' => ($uniquedExpandedIgnoreErrors[$key]['count'] ?? 1) + ($ignoreError['count'] ?? 1), 'reportUnmatched' => ($uniquedExpandedIgnoreErrors[$key]['reportUnmatched'] ?? $this->reportUnmatchedIgnoredErrors) || ($ignoreError['reportUnmatched'] ?? $this->reportUnmatchedIgnoredErrors)];
        }
        $expandedIgnoreErrors = array_values($uniquedExpandedIgnoreErrors);
        foreach ($expandedIgnoreErrors as $i => $ignoreError) {
            $ignoreErrorEntry = ['index' => $i, 'ignoreError' => $ignoreError];
            try {
                if (is_array($ignoreError)) {
                    if (!isset($ignoreError['message'])) {
                        $errors[] = sprintf('Ignored error %s is missing a message.', Json::encode($ignoreError));
                        continue;
                    }
                    if (!isset($ignoreError['path'])) {
                        $otherIgnoreErrors[] = $ignoreErrorEntry;
                    } elseif (@is_file($ignoreError['path'])) {
                        $normalizedPath = $this->fileHelper->normalizePath($ignoreError['path']);
                        $ignoreError['path'] = $normalizedPath;
                        $ignoreErrorsByFile[$normalizedPath][] = $ignoreErrorEntry;
                        $ignoreError['realPath'] = $normalizedPath;
                        $expandedIgnoreErrors[$i] = $ignoreError;
                    } else {
                        $otherIgnoreErrors[] = $ignoreErrorEntry;
                    }
                } else {
                    $otherIgnoreErrors[] = $ignoreErrorEntry;
                }
            } catch (JsonException $e) {
                $errors[] = $e->getMessage();
            }
        }
        return new \PHPStan\Analyser\IgnoredErrorHelperResult($this->fileHelper, $errors, $otherIgnoreErrors, $ignoreErrorsByFile, $expandedIgnoreErrors, $this->reportUnmatchedIgnoredErrors);
    }
}
