<?php

declare(strict_types=1);

namespace SuperKernel\Bootstrap;

use SuperKernel\Bootstrap\Composer\ComposerJson;
use SuperKernel\Bootstrap\Root\ProjectRoot;

use function array_key_last;
use function array_pop;
use function explode;
use function implode;
use function is_file;
use function ltrim;
use function preg_match;
use function preg_replace;
use function rtrim;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function substr;

final readonly class Paths
{
    public function __construct(
        private string $rootPath,
        private string $vendorPath,
        private string $composerJsonPath,
        private ?string $composerLockPath,
    ) {
    }

    public static function fromProjectRoot(ProjectRoot $projectRoot, ComposerJson $composerJson): self
    {
        $rootPath = self::normalize($projectRoot->path());
        $composerLockPath = self::join($rootPath, 'composer.lock');

        return new self(
            rootPath: $rootPath,
            vendorPath: self::resolve($rootPath, $composerJson->vendorDir()),
            composerJsonPath: $projectRoot->markerPath(),
            composerLockPath: is_file($composerLockPath) ? $composerLockPath : null,
        );
    }

    public function rootPath(): string
    {
        return $this->rootPath;
    }

    public function vendorPath(): string
    {
        return $this->vendorPath;
    }

    public function composerJsonPath(): string
    {
        return $this->composerJsonPath;
    }

    public function composerLockPath(): ?string
    {
        return $this->composerLockPath;
    }

    private static function resolve(string $rootPath, string $path): string
    {
        if (self::isAbsolute($path) || str_contains($path, '://')) {
            return self::normalize($path);
        }

        return self::join($rootPath, $path);
    }

    private static function join(string $left, string $right): string
    {
        return self::normalize(rtrim($left, '/\\') . '/' . ltrim($right, '/\\'));
    }

    private static function normalize(string $path): string
    {
        if (str_starts_with($path, 'phar://')) {
            return 'phar://' . self::normalizePathSegments(substr($path, 7));
        }

        return self::normalizePathSegments($path);
    }

    private static function normalizePathSegments(string $path): string
    {
        $path = preg_replace('#/+#', '/', str_replace('\\', '/', $path)) ?? $path;
        $drivePrefix = '';

        if (preg_match('/^[A-Za-z]:/', $path) === 1) {
            $drivePrefix = substr($path, 0, 2);
            $path = substr($path, 2);
        }

        $absolute = str_starts_with($path, '/');
        $segments = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                $lastIndex = array_key_last($segments);

                if ($lastIndex !== null && $segments[$lastIndex] !== '..') {
                    array_pop($segments);
                    continue;
                }

                if (!$absolute) {
                    $segments[] = $segment;
                }

                continue;
            }

            $segments[] = $segment;
        }

        $normalized = implode('/', $segments);

        if ($absolute) {
            $normalized = '/' . $normalized;
        }

        if ($drivePrefix !== '') {
            return $drivePrefix . ($normalized === '' ? '/' : $normalized);
        }

        if ($normalized === '') {
            return $absolute ? '/' : '.';
        }

        return $normalized;
    }

    private static function isAbsolute(string $path): bool
    {
        return str_starts_with($path, '/')
            || (bool) preg_match('/^[A-Za-z]:[\/\\\\]/', $path);
    }
}
