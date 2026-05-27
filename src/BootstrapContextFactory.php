<?php

declare(strict_types=1);

namespace SuperKernel\Bootstrap;

use SuperKernel\Bootstrap\Composer\ComposerMetadataReader;
use SuperKernel\Bootstrap\Contract\RootResolverInterface;
use SuperKernel\Bootstrap\Root\RootResolver;

final readonly class BootstrapContextFactory
{
    public function __construct(
        private RootResolverInterface $rootResolver = new RootResolver(),
        private ComposerMetadataReader $composerMetadataReader = new ComposerMetadataReader(),
    ) {
    }

    public static function create(?string $startPath = null): BootstrapContext
    {
        return (new self())->fromStartPath($startPath);
    }

    public function fromStartPath(?string $startPath = null): BootstrapContext
    {
        $projectRoot = $this->rootResolver->resolve($startPath);
        $composerJson = $this->composerMetadataReader->readJson($projectRoot->markerPath());
        $paths = Paths::fromProjectRoot($projectRoot, $composerJson);

        return new BootstrapContext(
            paths: $paths,
            composerJson: $composerJson,
            composerLock: $this->composerMetadataReader->readLock($paths->composerLockPath()),
            bootstrapEnvironment: BootstrapEnvironment::current(),
        );
    }
}
