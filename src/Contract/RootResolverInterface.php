<?php

declare(strict_types=1);

namespace SuperKernel\Bootstrap\Contract;

use SuperKernel\Bootstrap\Root\ProjectRoot;

interface RootResolverInterface
{
    public function resolve(?string $startPath = null): ProjectRoot;
}
