<?php

declare(strict_types=1);

namespace SuperKernel\Bootstrap\Tests;

use PHPUnit\Framework\TestCase;
use SuperKernel\Bootstrap\BootstrapContextFactory;

use function bin2hex;
use function file_put_contents;
use function is_dir;
use function json_encode;
use function mkdir;
use function random_bytes;
use function rmdir;
use function scandir;
use function sys_get_temp_dir;
use function unlink;

use const JSON_THROW_ON_ERROR;

final class BootstrapContextFactoryTest extends TestCase
{
    private string $projectPath;

    protected function setUp(): void
    {
        $this->projectPath = sys_get_temp_dir() . '/super-kernel-bootstrap-' . bin2hex(random_bytes(6));
        mkdir($this->projectPath . '/app/Console', recursive: true);

        file_put_contents($this->projectPath . '/composer.json', json_encode([
            'name' => 'super-kernel/example',
            'config' => [
                'vendor-dir' => 'build/vendor',
            ],
        ], JSON_THROW_ON_ERROR));

        file_put_contents($this->projectPath . '/composer.lock', json_encode([
            'content-hash' => 'hash',
            'packages' => [
                [
                    'name' => 'swoole/ide-helper',
                    'version' => '6.0.0',
                ],
            ],
        ], JSON_THROW_ON_ERROR));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectPath);
    }

    public function testCreatesBootstrapContextFromNestedStartPath(): void
    {
        $bootstrapContext = BootstrapContextFactory::create($this->projectPath . '/app/Console');

        $paths = $bootstrapContext->paths();
        $composerJson = $bootstrapContext->composerJson();
        $composerLock = $bootstrapContext->composerLock();

        self::assertSame($this->projectPath, $paths->rootPath());
        self::assertSame($this->projectPath . '/build/vendor', $paths->vendorPath());
        self::assertSame($this->projectPath . '/composer.json', $paths->composerJsonPath());
        self::assertSame($this->projectPath . '/composer.lock', $paths->composerLockPath());
        self::assertSame('super-kernel/example', $composerJson->name());
        self::assertNotNull($composerLock);
        self::assertTrue($composerLock->hasPackage('swoole/ide-helper'));
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);

        self::assertIsArray($items);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $path . '/' . $item;

            if (is_dir($itemPath)) {
                $this->removeDirectory($itemPath);
                continue;
            }

            unlink($itemPath);
        }

        rmdir($path);
    }
}
