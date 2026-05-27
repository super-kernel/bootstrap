<?php

declare(strict_types=1);

namespace SuperKernel\Bootstrap;

use Phar;

use function array_any;
use function getcwd;
use function getenv;
use function is_string;

final readonly class BootstrapEnvironment
{
    public function __construct(
        private string $workingDirectory,
        private ?string $scriptFilename,
        private ?string $pharPath,
        private bool $composerScript,
    ) {
    }

    public static function current(): self
    {
        $workingDirectory = getcwd();
        $pharPath = Phar::running(false);
        $scriptFilename = $_SERVER['SCRIPT_FILENAME'] ?? $_SERVER['argv'][0] ?? null;

        return new self(
            workingDirectory: $workingDirectory === false ? '' : $workingDirectory,
            scriptFilename: is_string($scriptFilename) ? $scriptFilename : null,
            pharPath: $pharPath === '' ? null : $pharPath,
            composerScript: self::detectComposerScript(),
        );
    }

    public function workingDirectory(): string
    {
        return $this->workingDirectory;
    }

    public function scriptFilename(): ?string
    {
        return $this->scriptFilename;
    }

    public function pharPath(): ?string
    {
        return $this->pharPath;
    }

    public function isPhar(): bool
    {
        return $this->pharPath !== null;
    }

    public function isComposerScript(): bool
    {
        return $this->composerScript;
    }

    private static function detectComposerScript(): bool
    {
        return array_any(
            ['COMPOSER_BINARY', 'COMPOSER_DEV_MODE', 'COMPOSER_PROCESS_TIMEOUT'],
            static fn (string $name): bool => getenv($name) !== false,
        );
    }
}
