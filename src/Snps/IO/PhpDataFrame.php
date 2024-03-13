<?php

namespace Dna\Snps\IO;

class PhpDataFrame
{
    private array $data = [];
    private array $columns = [];

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->columns = array_keys($data[0]);
            $this->data = $data;
        }
    }

    public static function fromFile(string $filePath): self
    {
        $rows = array_map('str_getcsv', file($filePath));
        $columns = array_shift($rows);
        $data = array_map(fn($row) => array_combine($columns, $row), $rows);
        return new self($data);
    }

    public function addRow(array $row): void
    {
        $this->data[] = $row;
    }

    public function removeRow(int $index): void
    {
        array_splice($this->data, $index, 1);
    }

    public function addColumn(string $columnName, array $values): void
    {
        foreach ($this->data as $index => $row) {
            $this->data[$index][$columnName] = $values[$index] ?? null;
        }
        if (!in_array($columnName, $this->columns)) {
            $this->columns[] = $columnName;
        }
    }

    public function removeColumn(string $columnName): void
    {
        foreach ($this->data as $index => $row) {
            unset($this->data[$index][$columnName]);
        }
        $this->columns = array_filter($this->columns, fn($column) => $column !== $columnName);
    }

    public function filter(callable $callback): self
    {
        $filteredData = array_filter($this->data, $callback);
        return new self(array_values($filteredData));
    }

    public function sum(string $columnName): float
    {
        return array_sum(array_column($this->data, $columnName));
    }

    public function average(string $columnName): float
    {
        $columnData = array_column($this->data, $columnName);
        return array_sum($columnData) / count($columnData);
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function getRow(int $index): ?array
    {
        return $this->data[$index] ?? null;
    }

    public function getColumn(string $columnName): array
    {
        return array_column($this->data, $columnName);
    }
}
