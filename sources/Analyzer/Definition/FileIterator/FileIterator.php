<?php

namespace SqlFtw\Analyzer\Context\FileIterator;

use IteratorAggregate;
use RuntimeException;
use function array_values;

abstract class FileIterator implements IteratorAggregate
{

    /** @var list<string> */
    private array $paths;

    /** @var null|callable(string): string */
    private $versionMapper;

    private int $index = 0;

    /**
     * @param list<string> $paths
     * @param null|callable(string): string $versionMapper
     */
    public function __construct(array $paths, ?callable $versionMapper = null)
    {
        $this->paths = array_values($paths);
        $this->versionMapper = $versionMapper;
    }

    /**
     * @return array{string, string}|null
     */
    protected function getNextFileContents(): ?array
    {
        $fileName = $this->paths[$this->index++] ?? null;
        if ($fileName === null) {
            return null;
        }
        $contents = file_get_contents($fileName);
        if ($contents === false) {
            $error = error_get_last();
            throw new RuntimeException("Could not read file {$fileName}: " . $error['message']); // todo: own exceptions
        }

        $version = $this->versionMapper ? ($this->versionMapper)($fileName) : $fileName;

        return [$version, $contents];
    }

}