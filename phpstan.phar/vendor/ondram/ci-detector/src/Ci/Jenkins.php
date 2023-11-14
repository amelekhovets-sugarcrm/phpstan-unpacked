<?php

declare (strict_types=1);
namespace _PHPStan_c6b09fbdf\OndraM\CiDetector\Ci;

use _PHPStan_c6b09fbdf\OndraM\CiDetector\CiDetector;
use _PHPStan_c6b09fbdf\OndraM\CiDetector\Env;
use _PHPStan_c6b09fbdf\OndraM\CiDetector\TrinaryLogic;
class Jenkins extends AbstractCi
{
    public static function isDetected(Env $env) : bool
    {
        return $env->get('JENKINS_URL') !== \false;
    }
    public function getCiName() : string
    {
        return CiDetector::CI_JENKINS;
    }
    public function isPullRequest() : TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function getBuildNumber() : string
    {
        return $this->env->getString('BUILD_NUMBER');
    }
    public function getBuildUrl() : string
    {
        return $this->env->getString('BUILD_URL');
    }
    public function getGitCommit() : string
    {
        return $this->env->getString('GIT_COMMIT');
    }
    public function getGitBranch() : string
    {
        return $this->env->getString('GIT_BRANCH');
    }
    public function getRepositoryName() : string
    {
        return '';
        // unsupported
    }
    public function getRepositoryUrl() : string
    {
        return $this->env->getString('GIT_URL');
    }
}