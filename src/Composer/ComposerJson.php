<?php

declare(strict_types=1);

namespace SuperKernel\Bootstrap\Composer;

use function array_filter;
use function array_values;
use function is_array;
use function is_string;
use function preg_match;
use function rtrim;
use function str_replace;
use function trim;

use const ARRAY_FILTER_USE_BOTH;

final readonly class ComposerJson
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        private array $raw,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function raw(): array
    {
        return $this->raw;
    }

    public function name(): ?string
    {
        return $this->nullableString('name');
    }

    public function type(): ?string
    {
        return $this->nullableString('type');
    }

    public function description(): ?string
    {
        return $this->nullableString('description');
    }

    /**
     * @return string|list<string>|null
     */
    public function license(): string|array|null
    {
        $license = $this->raw['license'] ?? null;

        if (is_string($license)) {
            return $license;
        }

        if (is_array($license)) {
            return array_values(array_filter($license, is_string(...)));
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function keywords(): array
    {
        return $this->stringList('keywords');
    }

    /**
     * @return array<string, string>
     */
    public function require(): array
    {
        return $this->stringMap('require');
    }

    /**
     * @return array<string, string>
     */
    public function requireDev(): array
    {
        return $this->stringMap('require-dev');
    }

    /**
     * @return array<string, mixed>
     */
    public function autoload(): array
    {
        return $this->arrayValue('autoload');
    }

    /**
     * @return array<string, mixed>
     */
    public function autoloadDev(): array
    {
        return $this->arrayValue('autoload-dev');
    }

    /**
     * @return array<string, mixed>
     */
    public function config(): array
    {
        return $this->arrayValue('config');
    }

    /**
     * @return array<string, mixed>
     */
    public function extra(): array
    {
        return $this->arrayValue('extra');
    }

    /**
     * @return array<string, mixed>
     */
    public function scripts(): array
    {
        return $this->arrayValue('scripts');
    }

    public function vendorDir(): string
    {
        $config = $this->config();
        $vendorDir = $config['vendor-dir'] ?? null;

        if (!is_string($vendorDir)) {
            return 'vendor';
        }

        $vendorDir = trim($vendorDir);

        if ($vendorDir === '') {
            return 'vendor';
        }

        $normalizedVendorDir = str_replace('\\', '/', $vendorDir);

        if ($normalizedVendorDir === '/' || preg_match('/^[A-Za-z]:\/$/', $normalizedVendorDir) === 1) {
            return $normalizedVendorDir;
        }

        return rtrim($vendorDir, '/\\');
    }

    private function nullableString(string $key): ?string
    {
        $value = $this->raw[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function arrayValue(string $key): array
    {
        $value = $this->raw[$key] ?? [];

        return is_array($value) ? $value : [];
    }

    /**
     * @return list<string>
     */
    private function stringList(string $key): array
    {
        $value = $this->raw[$key] ?? [];

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, is_string(...)));
    }

    /**
     * @return array<string, string>
     */
    private function stringMap(string $key): array
    {
        $value = $this->raw[$key] ?? [];

        if (!is_array($value)) {
            return [];
        }

        return array_filter(
            $value,
            static fn (mixed $item, mixed $name): bool => is_string($name) && is_string($item),
            ARRAY_FILTER_USE_BOTH,
        );
    }
}
