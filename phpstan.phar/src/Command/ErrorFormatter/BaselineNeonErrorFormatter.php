<?php

declare (strict_types=1);
namespace PHPStan\Command\ErrorFormatter;

use _PHPStan_c6b09fbdf\Nette\DI\Helpers;
use _PHPStan_c6b09fbdf\Nette\Neon\Neon;
use _PHPStan_c6b09fbdf\Nette\Utils\Strings;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;
use PHPStan\ShouldNotHappenException;
use function ksort;
use function preg_quote;
use function substr;
use const SORT_STRING;
class BaselineNeonErrorFormatter
{
    /**
     * @var \PHPStan\File\RelativePathHelper
     */
    private $relativePathHelper;
    public function __construct(RelativePathHelper $relativePathHelper)
    {
        $this->relativePathHelper = $relativePathHelper;
    }
    public function formatErrors(AnalysisResult $analysisResult, Output $output, string $existingBaselineContent) : int
    {
        if (!$analysisResult->hasErrors()) {
            $output->writeRaw($this->getNeon([], $existingBaselineContent));
            return 0;
        }
        $fileErrors = [];
        foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
            if (!$fileSpecificError->canBeIgnored()) {
                continue;
            }
            $fileErrors[$this->relativePathHelper->getRelativePath($fileSpecificError->getFilePath())][] = $fileSpecificError->getMessage();
        }
        ksort($fileErrors, SORT_STRING);
        $errorsToOutput = [];
        foreach ($fileErrors as $file => $errorMessages) {
            $fileErrorsCounts = [];
            foreach ($errorMessages as $errorMessage) {
                if (!isset($fileErrorsCounts[$errorMessage])) {
                    $fileErrorsCounts[$errorMessage] = 1;
                    continue;
                }
                $fileErrorsCounts[$errorMessage]++;
            }
            ksort($fileErrorsCounts, SORT_STRING);
            foreach ($fileErrorsCounts as $message => $count) {
                $errorsToOutput[] = ['message' => Helpers::escape('#^' . preg_quote($message, '#') . '$#'), 'count' => $count, 'path' => Helpers::escape($file)];
            }
        }
        $output->writeRaw($this->getNeon($errorsToOutput, $existingBaselineContent));
        return 1;
    }
    /**
     * @param array<int, array{message: string, count: int, path: string}> $ignoreErrors
     */
    private function getNeon(array $ignoreErrors, string $existingBaselineContent) : string
    {
        $neon = Neon::encode(['parameters' => ['ignoreErrors' => $ignoreErrors]], Neon::BLOCK);
        if (substr($neon, -2) !== "\n\n") {
            throw new ShouldNotHappenException();
        }
        if ($existingBaselineContent === '') {
            return substr($neon, 0, -1);
        }
        $existingBaselineContentEndOfFileNewlinesMatches = Strings::match($existingBaselineContent, "~(\n)+\$~");
        $existingBaselineContentEndOfFileNewlines = $existingBaselineContentEndOfFileNewlinesMatches !== null ? $existingBaselineContentEndOfFileNewlinesMatches[0] : '';
        return substr($neon, 0, -2) . $existingBaselineContentEndOfFileNewlines;
    }
}
