<?php

declare (strict_types=1);
namespace PHPStan\Process;

use _PHPStan_c6b09fbdf\Fidry\CpuCoreCounter\CpuCoreCounter as FidryCpuCoreCounter;
use _PHPStan_c6b09fbdf\Fidry\CpuCoreCounter\NumberOfCpuCoreNotFound;
class CpuCoreCounter
{
    /**
     * @var int|null
     */
    private $count;
    public function getNumberOfCpuCores() : int
    {
        if ($this->count !== null) {
            return $this->count;
        }
        try {
            $this->count = (new FidryCpuCoreCounter())->getCount();
        } catch (NumberOfCpuCoreNotFound $exception) {
            $this->count = 1;
        }
        return $this->count;
    }
}
