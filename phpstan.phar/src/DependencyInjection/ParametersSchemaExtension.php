<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection;

use _PHPStan_c6b09fbdf\Nette\DI\CompilerExtension;
use _PHPStan_c6b09fbdf\Nette\DI\Definitions\Statement;
use _PHPStan_c6b09fbdf\Nette\Schema\Expect;
use _PHPStan_c6b09fbdf\Nette\Schema\Schema;
class ParametersSchemaExtension extends CompilerExtension
{
    public function getConfigSchema() : Schema
    {
        return Expect::arrayOf(Expect::type(Statement::class))->min(1);
    }
}
