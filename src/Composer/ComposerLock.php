<?php

declare(strict_types=1);

namespace SuperKernel\Bootstrap\Composer;

use function is_array;
use function is_string;

final readonly class ComposerLock
{
    /**
     * @param array<string, mixed> $raw
     * @param list<ComposerPackage> $packages
     * @param list<ComposerPackage> $packagesDev
     */
    private function __construct(
        private array $raw,
        private array $packages,
        private array $packagesDev,
    ) {
    }

    /**
     * @param array<string, mixed> $raw
     */
    public static function fromRaw(array $raw): self
    {
        return new self(
            raw: $raw,
            packages: self::packagesFromRaw($raw['packages'] ?? []),
            packagesDev: self::packagesFromRaw($raw['packages-dev'] ?? []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function raw(): array
    {
        return $this->raw;
    }

    public function contentHash(): ?string
    {
        $contentHash = $this->raw['content-hash'] ?? null;

        return is_string($contentHash) ? $contentHash : null;
    }

    public function pluginApiVersion(): ?string
    {
        $pluginApiVersion = $this->raw['plugin-api-version'] ?? null;

        return is_string($pluginApiVersion) ? $pluginApiVersion : null;
    }

    /**
     * @return list<ComposerPackage>
     */
    public function packages(): array
    {
        return $this->packages;
    }

    /**
     * @return list<ComposerPackage>
     */
    public function packagesDev(): array
    {
        return $this->packagesDev;
    }

    public function package(string $name): ?ComposerPackage
    {
        foreach ([...$this->packages, ...$this->packagesDev] as $composerPackage) {
            if ($composerPackage->name() === $name) {
                return $composerPackage;
            }
        }

        return null;
    }

    public function hasPackage(string $name): bool
    {
        return $this->package($name) !== null;
    }

    /**
     * @param mixed $packages
     *
     * @return list<ComposerPackage>
     */
    private static function packagesFromRaw(mixed $packages): array
    {
        if (!is_array($packages)) {
            return [];
        }

        $composerPackages = [];

        foreach ($packages as $package) {
            if (is_array($package)) {
                $composerPackages[] = new ComposerPackage($package);
            }
        }

        return $composerPackages;
    }
}
