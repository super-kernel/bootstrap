<?php

declare(strict_types=1);

namespace SuperKernel\Bootstrap\Tests\Root;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SuperKernel\Bootstrap\Exception\ProjectRootNotFoundException;
use SuperKernel\Bootstrap\Root\RootResolver;

use function bin2hex;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function random_bytes;
use function rmdir;
use function scandir;
use function sys_get_temp_dir;
use function unlink;

final class RootResolverTest extends TestCase
{
    private string $projectPath;

    protected function setUp(): void
    {
        $this->projectPath = sys_get_temp_dir() . '/super-kernel-root-' . bin2hex(random_bytes(6));
        mkdir($this->projectPath . '/src/Nested', recursive: true);
        file_put_contents($this->projectPath . '/composer.json', '{}');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectPath);
    }

    public function testResolvesProjectRootFromNestedDirectory(): void
    {
        $rootResolver = new RootResolver();
        $projectRoot = $rootResolver->resolve($this->projectPath . '/src/Nested');

        self::assertSame($this->projectPath, $projectRoot->path());
        self::assertSame($this->projectPath . '/composer.json', $projectRoot->markerPath());
    }

    public function testResolvesProjectRootFromNestedFile(): void
    {
        $entryFile = $this->projectPath . '/src/Nested/Command.php';
        file_put_contents($entryFile, '<?php');

        $projectRoot = (new RootResolver())->resolve($entryFile);

        self::assertSame($this->projectPath, $projectRoot->path());
        self::assertSame($this->projectPath . '/composer.json', $projectRoot->markerPath());
    }

    public function testResolvesProjectRootFromPharUriByArchivePath(): void
    {
        file_put_contents($this->projectPath . '/app.phar', '');

        $projectRoot = (new RootResolver())->resolve('phar://' . $this->projectPath . '/app.phar/src/Kernel.php');

        self::assertSame($this->projectPath, $projectRoot->path());
        self::assertSame($this->projectPath . '/composer.json', $projectRoot->markerPath());
    }

    public function testResolvesPharUriWhenParentDirectoryNameContainsPhar(): void
    {
        $rootPath = $this->projectPath . '/build.phar.d';
        mkdir($rootPath);
        file_put_contents($rootPath . '/composer.json', '{}');

        $projectRoot = (new RootResolver())->resolve('phar://' . $rootPath . '/app.phar/src/Kernel.php');

        self::assertSame($rootPath, $projectRoot->path());
        self::assertSame($rootPath . '/composer.json', $projectRoot->markerPath());
    }

    public function testThrowsWhenRootCannotBeFound(): void
    {
        $this->removeDirectory($this->projectPath);
        mkdir($this->projectPath . '/empty', recursive: true);

        $this->expectException(ProjectRootNotFoundException::class);

        (new RootResolver())->resolve($this->projectPath . '/empty');
    }

    public function testRootNotFoundMessageContainsConfiguredMarkers(): void
    {
        $this->removeDirectory($this->projectPath);
        mkdir($this->projectPath . '/empty', recursive: true);

        $this->expectException(ProjectRootNotFoundException::class);
        $this->expectExceptionMessage('super-kernel.json');

        (new RootResolver(['super-kernel.json']))->resolve($this->projectPath . '/empty');
    }

    public function testRejectsEmptyMarkers(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RootResolver([]);
    }

    public function testRejectsBlankMarkers(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RootResolver(['']);
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
