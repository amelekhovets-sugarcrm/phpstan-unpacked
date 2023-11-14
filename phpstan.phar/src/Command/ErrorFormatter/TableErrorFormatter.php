<?php

declare (strict_types=1);
namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Analyser\Error;
use PHPStan\Command\AnalyseCommand;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;
use PHPStan\File\SimpleRelativePathHelper;
use _PHPStan_c6b09fbdf\Symfony\Component\Console\Formatter\OutputFormatter;
use function array_map;
use function count;
use function explode;
use function getenv;
use function is_string;
use function ltrim;
use function sprintf;
use function str_contains;
use function str_replace;
class TableErrorFormatter implements \PHPStan\Command\ErrorFormatter\ErrorFormatter
{
    /**
     * @var \PHPStan\File\RelativePathHelper
     */
    private $relativePathHelper;
    /**
     * @var \PHPStan\File\SimpleRelativePathHelper
     */
    private $simpleRelativePathHelper;
    /**
     * @var \PHPStan\Command\ErrorFormatter\CiDetectedErrorFormatter
     */
    private $ciDetectedErrorFormatter;
    /**
     * @var bool
     */
    private $showTipsOfTheDay;
    /**
     * @var string|null
     */
    private $editorUrl;
    /**
     * @var string|null
     */
    private $editorUrlTitle;
    public function __construct(RelativePathHelper $relativePathHelper, SimpleRelativePathHelper $simpleRelativePathHelper, \PHPStan\Command\ErrorFormatter\CiDetectedErrorFormatter $ciDetectedErrorFormatter, bool $showTipsOfTheDay, ?string $editorUrl, ?string $editorUrlTitle)
    {
        $this->relativePathHelper = $relativePathHelper;
        $this->simpleRelativePathHelper = $simpleRelativePathHelper;
        $this->ciDetectedErrorFormatter = $ciDetectedErrorFormatter;
        $this->showTipsOfTheDay = $showTipsOfTheDay;
        $this->editorUrl = $editorUrl;
        $this->editorUrlTitle = $editorUrlTitle;
    }
    /** @api */
    public function formatErrors(AnalysisResult $analysisResult, Output $output) : int
    {
        $this->ciDetectedErrorFormatter->formatErrors($analysisResult, $output);
        $projectConfigFile = 'phpstan.neon';
        if ($analysisResult->getProjectConfigFile() !== null) {
            $projectConfigFile = $this->relativePathHelper->getRelativePath($analysisResult->getProjectConfigFile());
        }
        $style = $output->getStyle();
        if (!$analysisResult->hasErrors() && !$analysisResult->hasWarnings()) {
            $style->success('No errors');
            if ($this->showTipsOfTheDay) {
                if ($analysisResult->isDefaultLevelUsed()) {
                    $output->writeLineFormatted('💡 Tip of the Day:');
                    $output->writeLineFormatted(sprintf("PHPStan is performing only the most basic checks.\nYou can pass a higher rule level through the <fg=cyan>--%s</> option\n(the default and current level is %d) to analyse code more thoroughly.", AnalyseCommand::OPTION_LEVEL, AnalyseCommand::DEFAULT_LEVEL));
                    $output->writeLineFormatted('');
                }
            }
            return 0;
        }
        /** @var array<string, Error[]> $fileErrors */
        $fileErrors = [];
        foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
            if (!isset($fileErrors[$fileSpecificError->getFile()])) {
                $fileErrors[$fileSpecificError->getFile()] = [];
            }
            $fileErrors[$fileSpecificError->getFile()][] = $fileSpecificError;
        }
        foreach ($fileErrors as $file => $errors) {
            $rows = [];
            foreach ($errors as $error) {
                $message = $error->getMessage();
                if ($error->getTip() !== null) {
                    $tip = $error->getTip();
                    $tip = str_replace('%configurationFile%', $projectConfigFile, $tip);
                    $message .= "\n";
                    if (str_contains($tip, "\n")) {
                        $lines = explode("\n", $tip);
                        foreach ($lines as $line) {
                            $message .= '💡 ' . ltrim($line, ' •') . "\n";
                        }
                    } else {
                        $message .= '💡 ' . $tip;
                    }
                }
                if (is_string($this->editorUrl)) {
                    $editorFile = $error->getTraitFilePath() ?? $error->getFilePath();
                    $url = str_replace(['%file%', '%relFile%', '%line%'], [$editorFile, $this->simpleRelativePathHelper->getRelativePath($editorFile), (string) $error->getLine()], $this->editorUrl);
                    if (is_string($this->editorUrlTitle)) {
                        $title = str_replace(['%file%', '%relFile%', '%line%'], [$editorFile, $this->simpleRelativePathHelper->getRelativePath($editorFile), (string) $error->getLine()], $this->editorUrlTitle);
                    } else {
                        $title = $this->relativePathHelper->getRelativePath($editorFile);
                    }
                    $message .= "\n✏️  <href=" . OutputFormatter::escape($url) . '>' . $title . '</>';
                }
                $rows[] = [$this->formatLineNumber($error->getLine()), $message];
            }
            $style->table(['Line', $this->relativePathHelper->getRelativePath($file)], $rows);
        }
        if (count($analysisResult->getNotFileSpecificErrors()) > 0) {
            $style->table(['', 'Error'], array_map(static function (string $error) : array {
                return ['', $error];
            }, $analysisResult->getNotFileSpecificErrors()));
        }
        $warningsCount = count($analysisResult->getWarnings());
        if ($warningsCount > 0) {
            $style->table(['', 'Warning'], array_map(static function (string $warning) : array {
                return ['', $warning];
            }, $analysisResult->getWarnings()));
        }
        $finalMessage = sprintf($analysisResult->getTotalErrorsCount() === 1 ? 'Found %d error' : 'Found %d errors', $analysisResult->getTotalErrorsCount());
        if ($warningsCount > 0) {
            $finalMessage .= sprintf($warningsCount === 1 ? ' and %d warning' : ' and %d warnings', $warningsCount);
        }
        if ($analysisResult->getTotalErrorsCount() > 0) {
            $style->error($finalMessage);
        } else {
            $style->warning($finalMessage);
        }
        return $analysisResult->getTotalErrorsCount() > 0 ? 1 : 0;
    }
    private function formatLineNumber(?int $lineNumber) : string
    {
        if ($lineNumber === null) {
            return '';
        }
        $isRunningInVSCodeTerminal = getenv('TERM_PROGRAM') === 'vscode';
        if ($isRunningInVSCodeTerminal) {
            return ':' . $lineNumber;
        }
        return (string) $lineNumber;
    }
}
