<?php

declare(strict_types=1);

namespace App\Shared;

use Iterator;

final class CsvIterator implements Iterator
{
    private const DEFAULT_CONFIG = ['length' => 1000, 'separator' => ','];

    private string $filePath;
    private array $config;
    private int $key = -1;

    /** @var resource|closed-resource|null */
    private $resource = null;
    private ?array $headers = null;
    private ?array $current;

    public function __construct(string $filePath, array $config = self::DEFAULT_CONFIG)
    {
        $this->filePath = $filePath;
        $this->config = array_replace_recursive(self::DEFAULT_CONFIG, $config);
    }

    public function rewind(): void
    {
        if (null !== $this->resource) {
            fclose($this->resource);
        }

        $this->resource = fopen($this->filePath, 'rb');
    }

    public function current()
    {
        if (null === $this->headers) {
            $this->headers = $this->readNext();
            $this->next();
        }

        return $this->current;
    }

    public function next(): void
    {
        ++$this->key;

        $current = $this->readNext();

        $this->current = ($current !== null) ? array_combine($this->headers, $current) : $current;
    }

    private function readNext(): ?array
    {
        $content = fgetcsv($this->resource, $this->config['length'], $this->config['separator']);

        if (false === $content) {
            return null;
        }

        return $content;
    }

    public function key(): int
    {
        return $this->key;
    }

    public function valid(): bool
    {
        return (null !== $this->headers
                && null !== $this->current)
            || (-1 === $this->key);
    }

    public function __destruct()
    {
        fclose($this->resource);
    }
}
