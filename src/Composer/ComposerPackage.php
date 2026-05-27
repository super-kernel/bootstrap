<?php

declare(strict_types=1);

namespace SuperKernel\Bootstrap\Composer;

use function array_filter;
use function is_array;
use function is_string;

use const ARRAY_FILTER_USE_BOTH;

final readonly class ComposerPackage
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

    public function version(): ?string
    {
        return $this->nullableString('version');
    }

    public function type(): ?string
    {
        return $this->nullableString('type');
    }

    /**
     * @return array<string, mixed>
     */
    public function source(): array
    {
        return $this->arrayValue('source');
    }

    /**
     * @return array<string, mixed>
     */
    public function dist(): array
    {
        return $this->arrayValue('dist');
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
    public function extra(): array
    {
        return $this->arrayValue('extra');
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
