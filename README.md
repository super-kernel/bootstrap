# Super Kernel Bootstrap

Bootstrap metadata resolver for Super Kernel.

This package resolves facts needed before the Swoole runtime starts:

- project root path, detected by the `composer.json` marker
- vendor path, including `config.vendor-dir`
- `composer.json` metadata
- optional `composer.lock` metadata
- current bootstrap environment facts

## Requirements

- PHP 8.4.x

## Installation

```bash
composer require super-kernel/bootstrap
```

## Usage

```php
use SuperKernel\Bootstrap\BootstrapContextFactory;

$bootstrapContext = BootstrapContextFactory::create();

$paths = $bootstrapContext->paths();
$composerJson = $bootstrapContext->composerJson();
$composerLock = $bootstrapContext->composerLock();

$rootPath = $paths->rootPath();
$vendorPath = $paths->vendorPath();
$packageName = $composerJson->name();

if ($composerLock !== null && $composerLock->hasPackage('swoole/ide-helper')) {
    // Package is installed.
}
```

## Behavior

- `composer.json` must exist and must decode to a JSON object.
- `composer.lock` is optional. Missing lock files resolve to `null`.
- `vendorPath()` respects `config.vendor-dir`, including relative paths, absolute paths, root paths, and `.` / `..` path segments.
- `RootResolver` can start from a directory, file path, or `phar://` URI and searches upward for the configured root marker.

## Development

```bash
composer install
composer test
composer cs
composer cs:fix
composer validate:composer
```

This package follows Semantic Versioning 2.0.0.
