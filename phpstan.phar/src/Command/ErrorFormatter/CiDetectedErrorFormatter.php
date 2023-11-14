<?php

declare (strict_types=1);
namespace PHPStan\Command\ErrorFormatter;

use _PHPStan_c6b09fbdf\OndraM\CiDetector\CiDetector;
use _PHPStan_c6b09fbdf\OndraM\CiDetector\Exception\CiNotDetectedException;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
/** @api */
class CiDetectedErrorFormatter implements \PHPStan\Command\ErrorFormatter\ErrorFormatter
{
    /**
     * @var \PHPStan\Command\ErrorFormatter\GithubErrorFormatter
     */
    private $githubErrorFormatter;
    /**
     * @var \PHPStan\Command\ErrorFormatter\TeamcityErrorFormatter
     */
    private $teamcityErrorFormatter;
    public function __construct(\PHPStan\Command\ErrorFormatter\GithubErrorFormatter $githubErrorFormatter, \PHPStan\Command\ErrorFormatter\TeamcityErrorFormatter $teamcityErrorFormatter)
    {
        $this->githubErrorFormatter = $githubErrorFormatter;
        $this->teamcityErrorFormatter = $teamcityErrorFormatter;
    }
    public function formatErrors(AnalysisResult $analysisResult, Output $output) : int
    {
        $ciDetector = new CiDetector();
        try {
            $ci = $ciDetector->detect();
            if ($ci->getCiName() === CiDetector::CI_GITHUB_ACTIONS) {
                return $this->githubErrorFormatter->formatErrors($analysisResult, $output);
            } elseif ($ci->getCiName() === CiDetector::CI_TEAMCITY) {
                return $this->teamcityErrorFormatter->formatErrors($analysisResult, $output);
            }
        } catch (CiNotDetectedException $exception) {
            // pass
        }
        if (!$analysisResult->hasErrors() && !$analysisResult->hasWarnings()) {
            return 0;
        }
        return $analysisResult->getTotalErrorsCount() > 0 ? 1 : 0;
    }
}
