<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Internal;

use RuntimeException;

use function Psl\File\read as read_file;
use function Psl\File\write as write_file;
use function Psl\Filesystem\exists;
use function Psl\Filesystem\is_directory;
use function Psl\Filesystem\is_writable;

/**
 * @internal
 */
final class Filesystem
{
    public function read(string $path): string
    {
        $path = $this->checkPathIsNonEmpty($path);
        return read_file($path);
    }

    public function write(string $path, string $content): void
    {
        if ($this->isDirectory($path)) {
            throw new RuntimeException("Path $path is a directory");
        }
        $path = $this->checkPathIsNonEmpty($path);
        write_file($path, $content);
    }

    public function isDirectory(string $path): bool
    {
        $path = $this->checkPathIsNonEmpty($path);
        return is_directory($path);
    }

    public function isWritable(string $path): bool
    {
        $path = $this->checkPathIsNonEmpty($path);
        return is_writable($path);
    }

    public function exists(string $path): bool
    {
        $path = $this->checkPathIsNonEmpty($path);
        return exists($path);
    }

    public function pathAbsoluteOrRelative(string $path, string $relativeTo): string
    {
        if ('' === $path) {
            return '';
        }

        if ($this->isAbsolute($path)) {
            return $path;
        }

        return rtrim($relativeTo, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $path;
    }

    private function isAbsolute(string $path): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return (bool) preg_match('#^[A-Za-z]:[/\\\\]#', $path);
        }

        return str_starts_with($path, DIRECTORY_SEPARATOR);
    }

    /** @return non-empty-string */
    private function checkPathIsNonEmpty(string $path): string
    {
        if ('' === $path) {
            throw new RuntimeException('Path cannot be empty');
        }

        return $path;
    }
}
