<?php

declare(strict_types=1);

namespace SuperKernel\Bootstrap\Composer;

use JsonException;
use stdClass;
use SuperKernel\Bootstrap\Exception\ComposerJsonNotFoundException;
use SuperKernel\Bootstrap\Exception\InvalidComposerJsonException;
use SuperKernel\Bootstrap\Exception\InvalidComposerLockException;

use function array_map;
use function file_get_contents;
use function get_object_vars;
use function is_array;
use function is_file;
use function json_decode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

final readonly class ComposerMetadataReader
{
    public function readJson(string $composerJsonPath): ComposerJson
    {
        if (!is_file($composerJsonPath)) {
            throw new ComposerJsonNotFoundException(sprintf('Composer json file was not found at path [%s].', $composerJsonPath));
        }

        try {
            $raw = $this->decodeFile($composerJsonPath);
        } catch (JsonException $exception) {
            throw new InvalidComposerJsonException(
                sprintf('Composer json file [%s] is not a valid JSON object.', $composerJsonPath),
                previous: $exception,
            );
        }

        return new ComposerJson($raw);
    }

    public function readLock(?string $composerLockPath): ?ComposerLock
    {
        if ($composerLockPath === null || !is_file($composerLockPath)) {
            return null;
        }

        try {
            return ComposerLock::fromRaw($this->decodeFile($composerLockPath));
        } catch (JsonException $exception) {
            throw new InvalidComposerLockException(
                sprintf('Composer lock file [%s] is not a valid JSON object.', $composerLockPath),
                previous: $exception,
            );
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    private function decodeFile(string $path): array
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new JsonException(sprintf('Unable to read JSON file [%s].', $path));
        }

        $decoded = json_decode($contents, flags: JSON_THROW_ON_ERROR);

        if (!$decoded instanceof stdClass) {
            throw new JsonException(sprintf('JSON file [%s] must decode to an object.', $path));
        }

        return $this->normalizeValue($decoded);
    }

    /**
     * @return mixed
     */
    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof stdClass) {
            return array_map($this->normalizeValue(...), get_object_vars($value));
        }

        if (is_array($value)) {
            return array_map($this->normalizeValue(...), $value);
        }

        return $value;
    }
}
