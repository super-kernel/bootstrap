<?php

declare(strict_types=1);

namespace SuperKernel\Bootstrap;

use SuperKernel\Bootstrap\Composer\ComposerJson;
use SuperKernel\Bootstrap\Composer\ComposerLock;

final readonly class BootstrapContext
{
    public function __construct(
        private Paths $paths,
        private ComposerJson $composerJson,
        private ?ComposerLock $composerLock,
        private BootstrapEnvironment $bootstrapEnvironment,
    ) {
    }

    public function paths(): Paths
    {
        return $this->paths;
    }

    public function composerJson(): ComposerJson
    {
        return $this->composerJson;
    }

    public function composerLock(): ?ComposerLock
    {
        return $this->composerLock;
    }

    public function bootstrapEnvironment(): BootstrapEnvironment
    {
        return $this->bootstrapEnvironment;
    }
}
