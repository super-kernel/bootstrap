<?php

declare(strict_types=1);

namespace SuperKernel\Bootstrap\Tests;

use PHPUnit\Framework\TestCase;
use SuperKernel\Bootstrap\Composer\ComposerJson;
use SuperKernel\Bootstrap\Paths;
use SuperKernel\Bootstrap\Root\ProjectRoot;

final class PathsTest extends TestCase
{
    public function testUsesDefaultVendorPathWhenVendorDirIsMissing(): void
    {
        $paths = Paths::fromProjectRoot(
            new ProjectRoot('/tmp/super-kernel/example', '/tmp/super-kernel/example/composer.json'),
            new ComposerJson([]),
        );

        self::assertSame('/tmp/super-kernel/example/vendor', $paths->vendorPath());
    }

    public function testResolvesRelativeVendorPathFromRoot(): void
    {
        $paths = Paths::fromProjectRoot(
            new ProjectRoot('/tmp/super-kernel/example', '/tmp/super-kernel/example/composer.json'),
            new ComposerJson([
                'config' => [
                    'vendor-dir' => 'build/vendor',
                ],
            ]),
        );

        self::assertSame('/tmp/super-kernel/example/build/vendor', $paths->vendorPath());
    }

    public function testNormalizesRelativeVendorPathSegments(): void
    {
        $paths = Paths::fromProjectRoot(
            new ProjectRoot('/tmp/super-kernel/example', '/tmp/super-kernel/example/composer.json'),
            new ComposerJson([
                'config' => [
                    'vendor-dir' => './build/../vendor',
                ],
            ]),
        );

        self::assertSame('/tmp/super-kernel/example/vendor', $paths->vendorPath());
    }

    public function testNormalizesParentVendorPathSegments(): void
    {
        $paths = Paths::fromProjectRoot(
            new ProjectRoot('/tmp/super-kernel/example', '/tmp/super-kernel/example/composer.json'),
            new ComposerJson([
                'config' => [
                    'vendor-dir' => '../vendor',
                ],
            ]),
        );

        self::assertSame('/tmp/super-kernel/vendor', $paths->vendorPath());
    }

    public function testPreservesAbsoluteVendorPath(): void
    {
        $paths = Paths::fromProjectRoot(
            new ProjectRoot('/tmp/super-kernel/example', '/tmp/super-kernel/example/composer.json'),
            new ComposerJson([
                'config' => [
                    'vendor-dir' => '/opt/super-kernel/vendor',
                ],
            ]),
        );

        self::assertSame('/opt/super-kernel/vendor', $paths->vendorPath());
    }

    public function testPreservesRootVendorPath(): void
    {
        $paths = Paths::fromProjectRoot(
            new ProjectRoot('/tmp/super-kernel/example', '/tmp/super-kernel/example/composer.json'),
            new ComposerJson([
                'config' => [
                    'vendor-dir' => '/',
                ],
            ]),
        );

        self::assertSame('/', $paths->vendorPath());
    }

    public function testPreservesWindowsDriveRootVendorPath(): void
    {
        $paths = Paths::fromProjectRoot(
            new ProjectRoot('/tmp/super-kernel/example', '/tmp/super-kernel/example/composer.json'),
            new ComposerJson([
                'config' => [
                    'vendor-dir' => 'C:\\',
                ],
            ]),
        );

        self::assertSame('C:/', $paths->vendorPath());
    }
}
