<?php

declare(strict_types=1);

namespace SuperKernel\Bootstrap\Root;

final readonly class ProjectRoot
{
    public function __construct(
        private string $path,
        private string $markerPath,
    ) {
    }

    public function path(): string
    {
        return $this->path;
    }

    public function markerPath(): string
    {
        return $this->markerPath;
    }
}
