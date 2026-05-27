<?php

declare(strict_types=1);

namespace SuperKernel\Bootstrap\Root;

use InvalidArgumentException;
use Phar;
use SuperKernel\Bootstrap\Contract\RootResolverInterface;
use SuperKernel\Bootstrap\Exception\ProjectRootNotFoundException;

use function array_any;
use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function dirname;
use function getcwd;
use function implode;
use function is_dir;
use function is_file;
use function is_string;
use function ltrim;
use function preg_match;
use function preg_replace;
use function realpath;
use function rtrim;
use function sprintf;
use function str_replace;
use function str_starts_with;
use function substr;
use function trim;

final readonly class RootResolver implements RootResolverInterface
{
    /**
     * @param list<string> $markers
     */
    public function __construct(
        private array $markers = ['composer.json'],
    ) {
        if ($this->markers === [] || array_any($this->markers, static fn (mixed $marker): bool => !is_string($marker) || trim($marker) === '')) {
            throw new InvalidArgumentException('Root markers must be a non-empty list of non-empty strings.');
        }
    }

    public function resolve(?string $startPath = null): ProjectRoot
    {
        foreach ($this->candidateDirectories($startPath) as $directory) {
            $projectRoot = $this->searchUpward($directory);

            if ($projectRoot !== null) {
                return $projectRoot;
            }
        }

        throw new ProjectRootNotFoundException(sprintf(
            'Unable to locate the project root markers [%s].',
            implode(', ', $this->markers),
        ));
    }

    /**
     * @return list<string>
     */
    private function candidateDirectories(?string $startPath): array
    {
        $candidates = [];

        if (is_string($startPath) && $startPath !== '') {
            return $this->normalizeCandidates($this->expandCandidate($startPath));
        }

        $pharPath = Phar::running(false);

        if ($pharPath !== '') {
            foreach ($this->expandCandidate('phar://' . $pharPath) as $candidate) {
                $candidates[] = $candidate;
            }

            foreach ($this->expandCandidate($pharPath) as $candidate) {
                $candidates[] = $candidate;
            }
        }

        foreach ([getcwd(), $_SERVER['SCRIPT_FILENAME'] ?? null, $_SERVER['argv'][0] ?? null] as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                foreach ($this->expandCandidate($candidate) as $expandedCandidate) {
                    $candidates[] = $expandedCandidate;
                }
            }
        }

        return $this->normalizeCandidates($candidates);
    }

    /**
     * @return list<string>
     */
    private function expandCandidate(string $candidate): array
    {
        $candidates = [$candidate];
        $candidate = $this->normalizePath($candidate);

        if (!str_starts_with($candidate, 'phar://')) {
            return $candidates;
        }

        $archivePath = $this->extractPharArchivePath($candidate);

        if ($archivePath === null) {
            return $candidates;
        }

        $candidates[] = $archivePath;
        $candidates[] = dirname($archivePath);

        return $candidates;
    }

    /**
     * @param list<string> $candidates
     *
     * @return list<string>
     */
    private function normalizeCandidates(array $candidates): array
    {
        return array_values(array_unique(array_filter(
            array_map($this->normalizeCandidate(...), $candidates),
            static fn (?string $candidate): bool => $candidate !== null && $candidate !== '',
        )));
    }

    private function normalizeCandidate(string $candidate): ?string
    {
        $candidate = $this->normalizePath($candidate);

        if (str_starts_with($candidate, 'phar://')) {
            return $this->pharDirectory($candidate);
        }

        $realPath = realpath($candidate);

        if ($realPath === false) {
            $realPath = realpath(dirname($candidate));
        }

        if ($realPath === false) {
            return null;
        }

        return is_dir($realPath) ? $this->normalizePath($realPath) : $this->normalizePath(dirname($realPath));
    }

    private function extractPharArchivePath(string $candidate): ?string
    {
        $path = $this->normalizePath(substr($candidate, 7));
        $current = $path;

        while ($current !== '' && $current !== '.' && $current !== '/') {
            if (is_file($current)) {
                return $this->normalizePath($current);
            }

            $parent = dirname($current);

            if ($parent === $current) {
                break;
            }

            $current = $parent;
        }

        if (preg_match('#^(.+?\.phar)(?:/|$)#', $path, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    private function searchUpward(string $directory): ?ProjectRoot
    {
        $directory = $this->normalizePath($directory);

        while ($directory !== '') {
            foreach ($this->markers as $marker) {
                $markerPath = $this->join($directory, $marker);

                if (is_file($markerPath)) {
                    return new ProjectRoot($directory, $markerPath);
                }
            }

            $parent = $this->parentDirectory($directory);

            if ($parent === $directory || $parent === null) {
                return null;
            }

            $directory = $parent;
        }

        return null;
    }

    private function pharDirectory(string $candidate): string
    {
        if (is_file($candidate)) {
            return dirname($candidate);
        }

        return $candidate;
    }

    private function parentDirectory(string $directory): ?string
    {
        if (str_starts_with($directory, 'phar://')) {
            $path = substr($directory, 7);
            $parent = dirname($path);

            return $parent === $path ? null : 'phar://' . $parent;
        }

        $parent = dirname($directory);

        return $parent === $directory ? null : $this->normalizePath($parent);
    }

    private function join(string $left, string $right): string
    {
        return rtrim($left, '/\\') . '/' . ltrim($right, '/\\');
    }

    private function normalizePath(string $path): string
    {
        if (str_starts_with($path, 'phar://')) {
            return 'phar://' . preg_replace('#/+#', '/', substr($path, 7));
        }

        return preg_replace('#/+#', '/', str_replace('\\', '/', $path)) ?? $path;
    }
}
