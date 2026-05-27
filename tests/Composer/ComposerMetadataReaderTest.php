<?php

declare(strict_types=1);

namespace SuperKernel\Bootstrap\Tests\Composer;

use PHPUnit\Framework\TestCase;
use SuperKernel\Bootstrap\Composer\ComposerMetadataReader;
use SuperKernel\Bootstrap\Exception\InvalidComposerJsonException;
use SuperKernel\Bootstrap\Exception\InvalidComposerLockException;

use function bin2hex;
use function file_put_contents;
use function glob;
use function json_encode;
use function mkdir;
use function random_bytes;
use function rmdir;
use function sys_get_temp_dir;
use function unlink;

use const JSON_THROW_ON_ERROR;

final class ComposerMetadataReaderTest extends TestCase
{
    private string $projectPath;

    protected function setUp(): void
    {
        $this->projectPath = sys_get_temp_dir() . '/super-kernel-composer-' . bin2hex(random_bytes(6));
        mkdir($this->projectPath);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->projectPath . '/*') ?: [] as $file) {
            unlink($file);
        }

        rmdir($this->projectPath);
    }

    public function testReadsComposerJsonMetadata(): void
    {
        file_put_contents($this->projectPath . '/composer.json', json_encode([
            'name' => 'super-kernel/example',
            'type' => 'library',
            'license' => 'MIT',
            'keywords' => ['super-kernel', 'bootstrap'],
            'require' => [
                'php' => '^8.4',
            ],
            'config' => [
                'vendor-dir' => 'var/vendor',
            ],
        ], JSON_THROW_ON_ERROR));

        $composerMetadataReader = new ComposerMetadataReader();
        $composerJson = $composerMetadataReader->readJson($this->projectPath . '/composer.json');

        self::assertSame('super-kernel/example', $composerJson->name());
        self::assertSame('library', $composerJson->type());
        self::assertSame('MIT', $composerJson->license());
        self::assertSame(['super-kernel', 'bootstrap'], $composerJson->keywords());
        self::assertSame(['php' => '^8.4'], $composerJson->require());
        self::assertSame('var/vendor', $composerJson->vendorDir());
        self::assertArrayHasKey('config', $composerJson->raw());
    }

    public function testReadsComposerLockMetadata(): void
    {
        file_put_contents($this->projectPath . '/composer.lock', json_encode([
            'content-hash' => 'abc',
            'plugin-api-version' => '2.6.0',
            'packages' => [
                [
                    'name' => 'psr/container',
                    'version' => '2.0.2',
                    'require' => [
                        'php' => '>=7.4',
                    ],
                ],
            ],
            'packages-dev' => [
                [
                    'name' => 'phpunit/phpunit',
                    'version' => '12.0.0',
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $composerMetadataReader = new ComposerMetadataReader();
        $composerLock = $composerMetadataReader->readLock($this->projectPath . '/composer.lock');

        self::assertNotNull($composerLock);
        self::assertSame('abc', $composerLock->contentHash());
        self::assertSame('2.6.0', $composerLock->pluginApiVersion());
        self::assertCount(1, $composerLock->packages());
        self::assertCount(1, $composerLock->packagesDev());
        self::assertTrue($composerLock->hasPackage('psr/container'));
        self::assertSame('2.0.2', $composerLock->package('psr/container')?->version());
    }

    public function testComposerJsonMustDecodeToObject(): void
    {
        file_put_contents($this->projectPath . '/composer.json', '[]');

        $this->expectException(InvalidComposerJsonException::class);

        (new ComposerMetadataReader())->readJson($this->projectPath . '/composer.json');
    }

    public function testComposerLockMustDecodeToObject(): void
    {
        file_put_contents($this->projectPath . '/composer.lock', '[]');

        $this->expectException(InvalidComposerLockException::class);

        (new ComposerMetadataReader())->readLock($this->projectPath . '/composer.lock');
    }

    public function testMissingComposerLockReturnsNull(): void
    {
        self::assertNull((new ComposerMetadataReader())->readLock($this->projectPath . '/composer.lock'));
    }
}
